<?php

namespace Pv\StaticsBundle\Asset;

class FileAsset
{
    private $path;
    private $content;

    public function __construct($path)
    {
        $this->path = $path;

        $this->content = file_get_contents($path);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }
}
