<?php

namespace Pv\Bundle\StaticsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\Bundle\StaticsBundle\Cache\FilesystemCache;

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

    function getFileContent($uri, $lang, $debug=false)
    {
        $params = array(
            'lang' => $lang,
            'debug' => $debug
        );

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
            throw new \Exception('Unknown file extension.');
        }
        $file->load();

        return $file;
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

    function get($path)
    {
        $cached = $this->getCached($path);
        if ($cached) {
            return $cached;
        }

        $paths = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths[] = $bundle->getPath().'/Resources/statics';
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext == 'less') {
            $file = new LessFile($path, $paths);
        } elseif ($ext == 'js') {
            $file = new JsFile($path, $paths);
            $file->setToolsDir($this->toolsDir);
        } else {
            return 'wrong file';
        }
        $file->load();
        $file->fullCompile();

        $this->cacheFile($file);

        return $file->getContent();
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
