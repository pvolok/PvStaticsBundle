<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\FileAsset;

class LessphpFilter
{
    public function filter(FileAsset $asset)
    {
        $content = $asset->getContent();

        $less = new \lessc();
        $content = $less->compile($content);

        $asset->setContent($content);
    }
}
