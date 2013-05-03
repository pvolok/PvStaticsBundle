<?php

namespace Pv\StaticsBundle\Asset;

class CachedAsset extends BaseAsset
{
    function __construct($uri, $content, $srcFiles)
    {
        parent::__construct($uri);

        $this->content = $content;
        $this->srcFiles = $srcFiles;
    }
}
