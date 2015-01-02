<?php

namespace Pv\StaticsBundle\Asset\Sprite;

class Config
{
    private $opts;

    public function __construct($opts = null)
    {
        $this->opts = $opts;
    }

    public function getType($scale)
    {
        return $this->getParam('type', $scale, 'png');
    }

    public function getQuality($scale)
    {
        return $this->getParam('quality', $scale, 90);
    }

    private function getParam($param, $scale, $default = null)
    {
        $scaleId = $scale . 'x';
        if (isset($this->opts[$scaleId][$param])) {
            return $this->opts[$scaleId][$param];
        }
        if (isset($this->opts[$param])) {
            return $this->opts[$param];
        }
        return $default;
    }
}
