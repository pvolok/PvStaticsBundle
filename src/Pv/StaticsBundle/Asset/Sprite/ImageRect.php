<?php

namespace Pv\StaticsBundle\Asset\Sprite;

class ImageRect
{
    private $name;
    private $image;

    public $x = 0;
    public $y = 0;

    public $hasBeenPositioned = false;

    function __construct($name, $image)
    {
        $this->name = $name;
        $this->image = $image;
    }

    function getName()
    {
        return $this->name;
    }

    function getImage()
    {
        return $this->image;
    }

    function getWidth()
    {
        return imagesx($this->image);
    }

    function getHeight()
    {
        return imagesy($this->image);
    }

    function setPosition($x, $y)
    {
        $this->hasBeenPositioned = true;
        $this->x = $x;
        $this->y = $y;
    }
}
