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

        if ($dependRegex = $this->getRequireRegex()) {
            $dependCb = function ($matches) use ($manager, $asset) {
                $depend = $manager->get($matches['url']);
                $asset->addChild($depend);
                return "// depend {$matches['url']}";
            };
            $content = preg_replace_callback($dependRegex, $dependCb, $content);
        }

        $cb = function($matches) use($manager, $asset) {
            $path = $manager->resolveUri($matches['url'], $asset);
            return $asset->getTop()->hasSrcFile($path) ?
                "// already included {$matches['url']}" :
                $manager->load($matches['url'], $asset)->getContent();
        };
        $content = preg_replace_callback($this->getRegex(), $cb, $content);

        $asset->setContent($content);
    }

    protected abstract function getRegex();

    protected function getRequireRegex()
    {
        return null;
    }
}
