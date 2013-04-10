<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\FileAsset;
use Pv\StaticsBundle\StaticsManager;

abstract class IncludeFilter
{
    private $manager;

    public function __construct(StaticsManager $manager)
    {
        $this->manager = $manager;
    }

    public function filter(FileAsset $asset)
    {
        $manager = $this->manager;
        $cwd = dirname($asset->getPath());
        $content = $asset->getContent();

        $cb = function($matches) use($manager, $cwd) {
            $child = $manager->load($matches['url'], $cwd);
            return $child->getContent();
        };
        $content = preg_replace_callback($this->getRegex(), $cb, $content);

        $asset->setContent($content);
    }

    protected abstract function getRegex();
}
