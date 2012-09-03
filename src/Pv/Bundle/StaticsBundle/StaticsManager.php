<?php

namespace Pv\Bundle\StaticsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\Bundle\StaticsBundle\Cache\FilesystemCache;

use Pv\Bundle\StaticsBundle\File\BaseFile;
use Pv\Bundle\StaticsBundle\File\LessFile;
use Pv\Bundle\StaticsBundle\File\JsFile;
use Pv\Bundle\StaticsBundle\File\JsLocFile;
use Pv\Bundle\StaticsBundle\File\SoyFile;

class StaticsManager
{
    private $container;
    private $cache;
    private $toolsDir;
    private $includePaths = array();

    function __construct(ContainerInterface $container, FilesystemCache $cache, $tools_dir)
    {
        $this->container = $container;
        $this->cache = $cache;
        $this->toolsDir = $tools_dir;

        // TODO: make it in service declaration
        $dirs = array();
        foreach ($container->get('kernel')->getBundles() as $bundle) {
            $dirs[] = $bundle->getPath();
        }
        $this->addBundlePath($dirs);
    }

    function addBundlePath($paths)
    {
        foreach ((array) $paths as $path) {
            array_unshift($this->includePaths, $path.'/Resources/statics');
        }
    }

    function getUrl($local_path)
    {
        $file = $this->create($local_path);
        if (!is_string($file)) {
            $file->getContent();
        }
    }

    function getFileContent($uri, $debug=false)
    {
        $params = array(
            'debug' => $debug
        );

        if (pathinfo($uri, PATHINFO_EXTENSION) == 'js') {
            if (preg_match('/\.(\w+)\.js$/', $uri, $matches)) {
                $params['locale'] = $matches[1];
                $uri = preg_replace('/\.(\w+)\.js$/', '.js', $uri);
            } else {
                throw new \Exception('js file must have locale in uri');
            }
        }

        $absPath = $this->resolvePath($uri);
        $content = $this->getCached($absPath, $params);
        if (!$content) {
            $file = $this->getFile($uri, $params);

            $content = $file->getContent();
        }

        return $content;
    }

    function getFile($uri, $params)
    {
        $file = $this->create($uri, $params);
        $file->fullCompile();
        $this->cacheFile($file);

        return $file;
    }

    function create($local_path, $params=array(), $parent_file=null)
    {
        $container = $this->container;
        $ext = pathinfo($local_path, PATHINFO_EXTENSION);

        $path = $this->resolvePath($local_path,
            $parent_file ? dirname($parent_file->getPath()) : null);
        if (!$path) {
            return null;
        }

        $file = null;
        if ($ext == 'js') {
            $file = new JsFile($path, $container, $this, $params);
        } elseif($ext == 'jsloc') {
            $file = new JsLocFile($path, $container, $this, $params);
        } elseif ($ext == 'soy') {
            $file = new SoyFile($path, $container, $this, $params);
        } elseif ($ext == 'less') {
            $file = new LessFile($path, $container, $this, $params);
        } else {
            $file = new BaseFile($path, $container, $this, $params);
        }
        $file->load();

        return $file;
    }

    function getPublicFiles()
    {
        $langs = $this->container->getParameter('locales');

        $files = array();

        foreach ($this->includePaths as $staticsDir) {
            if (!is_dir($staticsDir)) {
                continue;
            }
            $dir_iter = new \RecursiveDirectoryIterator($staticsDir, \FilesystemIterator::SKIP_DOTS);
            $dir_iter = new \RecursiveIteratorIterator($dir_iter);
            $cut_len = strlen($staticsDir) + 1;
            foreach ($dir_iter as $file) {
                $file = substr($file, $cut_len);
                if ($this->isPublicFile($file)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'js') {
                        foreach ($langs as $lang) {
                            $files[] = preg_replace('/\.js$/', ".$lang.js", $file);
                        }
                    } else {
                        $files[] = $file;
                    }
                }
            }
        }

        $files = array_unique($files);
        return $files;
    }

    private function isPublicFile($path)
    {
        if (preg_match('/(\/|^)_/', $path)) {
            return false;
        }

        return true;
    }

    private function resolvePath($local_path, $curDir=null)
    {
        if ($curDir && file_exists($curDir.'/'.$local_path)) {
            return $curDir.'/'.$local_path;
        }
        foreach ($this->includePaths as $includePath) {
            $abs_path = $includePath.'/'.$local_path;
            if (file_exists($abs_path)) {
                return $abs_path;
            }
        }
        return null;
    }

    protected function getCached($path, $params)
    {
        $cache = $this->cache;
        $meta_key = $this->getCacheKey($path, $params, 'meta');
        $content_key = $this->getCacheKey($path, $params);
        if (!$cache->has($meta_key) || !$cache->has($content_key)) {
            return null;
        }
        $metadata = json_decode($cache->get($meta_key), true);

        $compiled_date = $metadata['date'];
        foreach ($metadata['src_files'] as $src_file) {
            if ($compiled_date < filemtime($src_file)) {
                return null;
            }
        }
        return $cache->get($content_key);
    }

    protected function cacheFile($file)
    {
        $meta_key = $this->getCacheKey($file->getPath(), $file->getParams(), 'meta');
        $content_key = $this->getCacheKey($file->getPath(), $file->getParams());
        $metadata = array(
            'date' => time(),
            'src_files' => $file->getSrcFiles()
        );

        $this->cache->set($meta_key, json_encode($metadata));
        $this->cache->set($content_key, $file->getContent());
    }

    protected function getCacheKey($path, $params, $suffix=null)
    {
        $key = md5($path.json_encode($params));
        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return $key;
    }
}
