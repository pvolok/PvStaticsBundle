<?php

namespace Pv\StaticsBundle\Loader;


use Pv\StaticsBundle\Asset\BaseAsset;
use Pv\StaticsBundle\Asset\FileAsset;
use Pv\StaticsBundle\Asset\StringAsset;
use Pv\StaticsBundle\Asset\SpriteAsset;
use Pv\StaticsBundle\StaticsManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class Loader
{
    protected $kernel;
    protected $staticsManager;

    public function __construct(KernelInterface $kernel,
        StaticsManager $staticsManager)
    {
        $this->kernel = $kernel;
        $this->staticsManager = $staticsManager;
    }

    public function load($uri, BaseAsset $parent = null)
    {
        $params = parse_url($uri, PHP_URL_QUERY);
        parse_str($params, $params);
        $uri = parse_url($uri, PHP_URL_PATH);

        $debug = isset($params['debug']) ? $params['debug']
            : ($parent ? $parent->getParam('debug') : null);

        $asset = null;
        if (preg_match('/^(sprites\/\w+)\.sprite$/', $uri, $matches)) {
            $path = $this->resolveUri($uri);
            $asset = new SpriteAsset($uri, $path);
        } elseif (preg_match('/^(sprites\/\w+)(@2x)?\.(png|less)$/', $uri, $matches)) {
            $path = $matches[1];
            /** @var SpriteAsset $spriteAsset */
            $spriteAsset = $this->staticsManager->get($path.'.sprite');

            $scale = $matches[2] ? 2 : 1;
            $ext = $matches[3];
            $asset = new StringAsset($uri);
            foreach ($spriteAsset->getSrcFiles() as $srcFile) {
                $asset->addSrcFile($srcFile);
            }
            if ($ext == 'png') {
                $asset->setContent($spriteAsset->getPng($scale));
            } elseif ($ext == 'less') {
                $imgUrls = [];
                foreach ($spriteAsset->getScales() as $scale) {
                    if ($debug) {
                        $imgUrls[$scale] = '/s/'.$path.
                            ($scale == 1 ? '' : "@{$scale}x").
                            '.png?'.http_build_query($params);
                    } else {
                        $imgUrls[$scale] = '/s/'.
                            md5($spriteAsset->getPng($scale)).'.png';
                    }
                }
                $asset->setContent($spriteAsset->getLess($imgUrls));
            }
        } else {
            $path = $this->resolveUri($uri, $parent);
            $uri = $this->fixUri($uri, $path);
            $asset = new FileAsset($uri, $path);
        }

        if ($asset) {
            $asset->setParent($parent);
            if ($parent) {
                $parent->addChild($asset);
            }
            foreach ($params as $name => $value) {
                $asset->setParam($name, $value);
            }
        }

        return $asset;
    }

    public function resolveUri($uri, BaseAsset $parent = null)
    {
        // First check bower components
        if (preg_match('/^bower\/(.*?)$/', $uri, $matches)) {
            $path = dirname($this->kernel->getRootDir()) . '/bower_components/'
                . $matches[1];
            if (!file_exists($path)) {
                throw new \Exception(
                    "Bower dependency cannot be found ($path).");
            }
            return $path;
        }

        $uri = preg_replace('/^(sprites\/\w+)\.\w+$/', '$1', $uri);

        $cwd = ($parent && $parent->getPath()) ? dirname($parent->getPath())
            : null;

        $dirs = $this->getDirs();

        if ($cwd) {
            array_unshift($dirs, $cwd);
        }

        foreach ($dirs as $dir) {
            $path = "$dir/$uri";
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        throw new \Exception("The file with uri ($uri) can not be found.");
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

        $finder = Finder::create()->directories()->in($dirs)
            ->path('/^sprites\//')->depth(1);
        foreach ($finder as $file) {
            $path = $file->getRelativePathname();
            /** @var SpriteAsset $spriteAsset */
            $spriteAsset = $this->staticsManager->load("{$path}.sprite");
            foreach ($spriteAsset->getScales() as $scale) {
                $scaleSuffix = $scale == 1 ? '' : "@{$scale}x";
                $files[] = "$path$scaleSuffix.png";
            }
        }

        return $files;
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
        if (preg_match('/^bower\//', $uri)) {
            return $uri;
        }
        preg_match('/\/Resources\/statics\/(.*)/', $path, $matches);
        return $matches[1];
    }
}
