<?php

namespace Pv\StaticsBundle;

use Pv\StaticsBundle\Asset\BaseAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\StaticsBundle\Cache\FilesystemCache;

class StaticsManager
{
    private $container;
    private $cache;

    function __construct(ContainerInterface $container, FilesystemCache $cache)
    {
        $this->container = $container;
        $this->cache = $cache;
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
        $cacheKey = md5($uri).'.php.meta';
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
}
