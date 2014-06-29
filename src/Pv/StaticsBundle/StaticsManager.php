<?php

namespace Pv\StaticsBundle;

use Pv\StaticsBundle\Asset\BaseAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\StaticsBundle\Cache\FilesystemCache;
use Symfony\Component\HttpKernel\KernelInterface;

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

        /** @var KernelInterface $kernel */
        $kernel = $container->get('kernel');
        // TODO: make it in service declaration
        $dirs = array();
        foreach ($kernel->getBundles() as $bundle) {
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

    function resolveUri($uri, $parent)
    {
        return $this->container->get('statics.loader')->resolveUri($uri, $parent);
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
        } elseif ($ext == 'soy') {
            $this->container->get('statics.filters.soy')->filter($asset);
        }

        return $asset;
    }

    function get($uri)
    {
        $cacheKey = basename($uri).'_'.md5($uri).'.php.meta';
        /** @var BaseAsset $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached && !$cached->hasChanged()) {
            return $cached;
        }

        $asset = $this->load($uri);

        $ext = pathinfo($asset->getUri(), PATHINFO_EXTENSION);
        if ($ext == 'less') {
            $this->container->get('statics.filters.less')->filter($asset);
        }
        if ($ext == 'js') {
            $this->container->get('statics.filters.closure_compiler')
                ->filter($asset);
        }

        $this->cache->set($cacheKey, $asset);

        return $asset;
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

    protected function getCacheKey($path, $params, $suffix=null)
    {
        $key = md5($path.json_encode($params));
        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return $key;
    }
}
