<?php

namespace Pv\StaticsBundle\Asset;

class FileAsset
{
    private $uri;
    private $path;
    private $content;

    public function __construct($uri, $path)
    {
        $this->uri = $uri;
        $this->path = $path;

        $this->content = file_get_contents($path);
    }

    /**
     * This uri can be used to apply filters.
     * Don't use it to load an asset.
     */
    public function getUri()
    {
        return $this->uri;
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
