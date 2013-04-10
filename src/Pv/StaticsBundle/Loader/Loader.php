<?php

namespace Pv\StaticsBundle\Loader;


use Pv\StaticsBundle\Asset\FileAsset;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Loader
{
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load($uri, $cwd = null)
    {
        if (strpos($uri, 'sprites/')) {
        } else {
            $path = $this->resolvePath($uri, $cwd);
            $asset = new FileAsset($path);
            return $asset;
        }
    }

    protected function resolvePath($uri, $cwd)
    {
        $dirs = array_map(function(BundleInterface $bundle) {
            return $bundle->getPath().'/Resources/statics';
        }, $this->kernel->getBundles());

        if ($cwd) {
            $dirs[] = $cwd;
        }
        $dirs = array_reverse($dirs);

        foreach ($dirs as $dir) {
            $path = "$dir/$uri";
            if (is_file($path)) {
                return $path;
            }
        }

        throw new \Exception("The file with uri ($uri) can not be found.");
    }
}
