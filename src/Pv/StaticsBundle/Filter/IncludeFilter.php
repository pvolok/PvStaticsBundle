<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\BaseAsset;
use Pv\StaticsBundle\StaticsManager;

abstract class IncludeFilter
{
    private $manager;

    public function __construct(StaticsManager $manager)
    {
        $this->manager = $manager;
    }

    public function filter(BaseAsset $asset)
    {
        $manager = $this->manager;
        $content = $asset->getContent();

        $cb = function($matches) use($manager, $asset) {
            $child = $manager->load($matches['url'], $asset);
            return $child->getContent();
        };
        $content = preg_replace_callback($this->getRegex(), $cb, $content);

        $asset->setContent($content);
    }

    protected abstract function getRegex();
}
