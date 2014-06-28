<?php

namespace Pv\StaticsBundle\Asset;


class BaseAsset
{
    protected $uri;
    protected $path;
    protected $content;
    protected $params;

    protected $srcFiles = array();

    /** @var BaseAsset */
    protected $parent;
    /** @var BaseAsset[] */
    protected $children = array();

    public function __construct($uri)
    {
        $this->uri = $uri;

        $this->params = array();
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

    public function getParam($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        } elseif ($this->parent) {
            return $this->parent->getParam($name);
        }
        return null;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function setParent($asset)
    {
        $this->parent = $asset;
    }

    public function getTop()
    {
        return $this->parent ? $this->parent->getTop() : $this;
    }

    public function addChild($asset)
    {
        $this->children[] = $asset;
    }

    public function addSrcFile($file)
    {
        $this->srcFiles[] = $file;
    }

    public function hasSrcFile($file)
    {
        if (in_array($file, $this->srcFiles)) {
            return true;
        }
        foreach ($this->children as $child) {
            if ($child->hasSrcFile($file)) {
                return true;
            }
        }
        return false;
    }

    public function getSrcFiles()
    {
        $ret = $this->srcFiles;

        foreach ($this->children as $child) {
            $ret = array_merge($ret, $child->getSrcFiles());
        }

        return $ret;
    }
}
