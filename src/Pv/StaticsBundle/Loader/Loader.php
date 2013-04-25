<?php

namespace Pv\StaticsBundle\Loader;


use Pv\StaticsBundle\Asset\BaseAsset;
use Pv\StaticsBundle\Asset\FileAsset;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class Loader
{
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load($uri, BaseAsset $parent = null)
    {
        $params = parse_url($uri, PHP_URL_QUERY);
        parse_str($params, $params);
        $uri = parse_url($uri, PHP_URL_PATH);

        $cwd = ($parent && $parent->getPath()) ? dirname($parent->getPath())
            : null;

        $asset = null;
        if (strpos($uri, 'sprites/') === 0) {
        } else {
            $path = $this->resolvePath($uri, $cwd);
            $uri = $this->fixUri($uri, $path);
            $asset = new FileAsset($uri, $path);
        }

        if ($asset) {
            $asset->setParent($parent);
            foreach ($params as $name => $value) {
                $asset->setParam($name, $value);
            }
        }

        return $asset;
    }

    public function findAll()
    {
        $dirs = $this->getDirs();

        $finder = Finder::create()->files()->in($dirs)->notPath('/(?:^|\/)_/')
            ->notPath('/^sprites\//');

        $files = array();
        foreach ($finder as $file) {
            /** @var $file SplFileInfo */
            $files[] = $file->getRelativePathname();
        }
        $files = array_unique($files);

        return $files;
    }

    protected function resolvePath($uri, $cwd)
    {
        $dirs = $this->getDirs();

        if ($cwd) {
            array_unshift($dirs, $cwd);
        }

        foreach ($dirs as $dir) {
            $path = "$dir/$uri";
            if (is_file($path)) {
                return $path;
            }
        }

        throw new \Exception("The file with uri ($uri) can not be found.");
    }

    protected function getDirs()
    {
        $dirs = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            $dir = $bundle->getPath().'/Resources/statics';
            if (is_dir($dir)) {
                $dirs[] = $dir;
            }
        }

        return array_reverse($dirs);
    }

    protected function fixUri($uri, $path)
    {
        preg_match('/\/Resources\/statics\/(.*)/', $path, $matches);
        return $matches[1];
    }
}
