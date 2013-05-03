<?php

namespace Pv\StaticsBundle\Loader;


use Pv\StaticsBundle\Asset\BaseAsset;
use Pv\StaticsBundle\Asset\FileAsset;
use Pv\StaticsBundle\Asset\StringAsset;
use Pv\StaticsBundle\Asset\SpriteAsset;
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

        $debug = isset($params['debug']) ? $params['debug']
            : ($parent ? $parent->getParam('debug') : null);

        $cwd = ($parent && $parent->getPath()) ? dirname($parent->getPath())
            : null;

        $asset = null;
        if (preg_match('/^(sprites\/\w+)\.sprite$/', $uri, $matches)) {
            $path = $this->resolvePath($matches[1], null);
            $asset = new SpriteAsset($uri, $path);
        } elseif (preg_match('/^(sprites\/\w+)\.(png|less)$/', $uri, $matches)) {
            $spriteAsset = $this->kernel->getContainer()
                ->get('statics.manager')->get($matches[1].'.sprite');
            $spriteData = json_decode($spriteAsset->getContent(), true);

            $ext = $matches[2];
            $asset = new StringAsset($uri);
            foreach ($spriteAsset->getSrcFiles() as $srcFile) {
                $asset->addSrcFile($srcFile);
            }
            if ($ext == 'png') {
                $asset->setContent(SpriteAsset::getPng($spriteData));
            } elseif ($ext == 'less') {
                if ($debug) {
                    $imgUrl = '/s/'.preg_replace('/\.less$/', '.png', $uri).'?'.http_build_query($params);
                } else {
                    $imgUrl = '/s/'.md5(SpriteAsset::getPng($spriteData)).'.png';
                }
                $asset->setContent(SpriteAsset::getLess($spriteData, $imgUrl));
            }
        } else {
            $path = $this->resolvePath($uri, $cwd);
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
            /** @var $file SplFileInfo */
            $files[] = $file->getRelativePathname().'.png';
        }

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
            if (file_exists($path)) {
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
