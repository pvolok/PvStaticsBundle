<?php

namespace Pv\StaticsBundle\Asset;

class FileAsset extends BaseAsset
{
    public function __construct($uri, $path)
    {
        parent::__construct($uri);

        $this->path = $path;
        $this->content = file_get_contents($path);
        $this->addSrcFile($path);
    }
}
