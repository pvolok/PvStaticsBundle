<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\FileAsset;

class LessphpFilter
{
    public function filter(FileAsset $asset)
    {
        $content = $asset->getContent();

        $less = new \lessc();

        if (!$asset->getParam('debug')) {
            $less->setFormatter('compressed');
        }

        $content = $less->compile($content);

        $asset->setContent($content);
    }
}
