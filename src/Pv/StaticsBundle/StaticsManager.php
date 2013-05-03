<?php

namespace Pv\StaticsBundle;

use Pv\StaticsBundle\Asset\BaseAsset;
use Pv\StaticsBundle\Asset\CachedAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\StaticsBundle\Cache\FilesystemCache;

use Pv\StaticsBundle\File\BaseFile;
use Pv\StaticsBundle\File\LessFile;
use Pv\StaticsBundle\File\ImageFile;
use Pv\StaticsBundle\File\JsFile;
use Pv\StaticsBundle\File\JsLocFile;
use Pv\StaticsBundle\File\SoyFile;
use Pv\StaticsBundle\File\SpriteFile;

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

    function getUrl($local_path, $debug=false)
    {
        if ($debug) {
            return '/s/'.$local_path;
        } else {
            $ext = pathinfo($local_path, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'less':
                case 'sass':
                case 'scss':
                    $ext = 'css';
            }
            if (!$ext && preg_match('/^_sprites\//', $local_path)) {
                $ext = 'png';
            }
            $public_name = md5($this->getFileContent($local_path)).'.'.$ext;
            return '/s/'.$public_name;
        }
    }

    function load($uri, $parent = null)
    {
        /** @var BaseAsset $asset */
        $asset = $this->container->get('statics.loader')->load($uri, $parent);

        $ext = pathinfo($asset->getUri(), PATHINFO_EXTENSION);
        if (in_array($ext, array('css', 'less'))) {
            $this->container->get('statics.filters.css_include')->filter($asset);
        }
        if ($ext == 'js') {
            $this->container->get('statics.filters.js_include')->filter($asset);
        }

        return $asset;
    }

    function get($uri)
    {
        $cacheKey = md5($uri);
        $cacheMeta = $this->cache->get($cacheKey.'.meta');
        $cacheData = $this->cache->get($cacheKey.'.data');
        if ($cacheMeta && $cacheData) {
            $cacheMeta = json_decode($cacheMeta, true);
            $changed = false;
            foreach ($cacheMeta['files'] as $file) {
                if ($cacheMeta['mtime'] < filemtime($file)) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                return new CachedAsset($uri, $cacheData, $cacheMeta['files']);
            }
        }

        $asset = $this->load($uri);

        $ext = pathinfo($asset->getUri(), PATHINFO_EXTENSION);
        if ($ext == 'less') {
            $this->container->get('statics.filters.lessphp')->filter($asset);
        }

        $meta = array(
            'mtime' => time(),
            'files' => $asset->getSrcFiles(),
        );
        $this->cache->set($cacheKey.'.meta', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->cache->set($cacheKey.'.data', $asset->getContent());

        return $asset;
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
        $uri = preg_replace('/(_sprites\/[_\-a-z]+?)\.png$/', '$1', $uri);

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
        $uri = $local_path;
        $container = $this->container;
        $ext = pathinfo($local_path, PATHINFO_EXTENSION);

        $path = $this->resolvePath($local_path,
            $parent_file ? dirname($parent_file->getPath()) : null);
        if (!$path) {
            return null;
        }

        $file = null;
        if ($ext == 'js') {
            $file = new JsFile($uri, $path, $container, $this, $params);
        } elseif($ext == 'jsloc') {
            $file = new JsLocFile($uri, $path, $container, $this, $params);
        } elseif ($ext == 'soy') {
            $file = new SoyFile($uri, $path, $container, $this, $params);
        } elseif ($ext == 'less') {
            $file = new LessFile($uri, $path, $container, $this, $params);
        } elseif (preg_match('/^_sprites\/\w+$/', $local_path)) {
            $file = new SpriteFile($uri, $path, $container, $this, $params);
        } elseif ($ext == 'png') {
            $file = new ImageFile($uri, $path, $container, $this, $params);
        } else {
            $file = new BaseFile($uri, $path, $container, $this, $params);
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
            // sprites
            foreach (glob($staticsDir.'/_sprites/*', GLOB_ONLYDIR) as $sprite) {
                $files[] = substr($sprite, strlen($staticsDir) + 1);
            }
        }

        $files = array_unique($files);
        return $files;
    }

    private function isPublicFile($path)
    {
        if (preg_match('/^_sprites\/[_\-a-z]+$/', $path)) {
            return true;
        }
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
            if (file_exists($abs_path) || is_dir($abs_path)) {
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
            if (!file_exists($src_file) || $compiled_date < filemtime($src_file)) {
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
