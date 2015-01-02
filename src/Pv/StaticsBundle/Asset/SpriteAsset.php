<?php

namespace Pv\StaticsBundle\Asset;

use Pv\StaticsBundle\Asset\Sprite\Arranger;
use Pv\StaticsBundle\Asset\Sprite\Config;
use Pv\StaticsBundle\Asset\Sprite\ImageRect;

class SpriteAsset extends BaseAsset
{
    /** @var Config */
    protected $config;
    protected $hasRetina = false;

    public static function isImage($ext)
    {
        return $ext == 'png' || $ext == 'jpeg';
    }

    function __construct($uri, $path)
    {
        parent::__construct($uri);

        $this->path = $path;
        $this->content = [];

        $this->loadConfig();
        $this->load();
    }

    public function getScales()
    {
        return $this->hasRetina ? [1, 2] : [1];
    }

    public function getImage($scale)
    {
        return $this->content[$scale]['img'];
    }

    public function getImageType($scale)
    {
        return $this->config->getType($scale);
    }

    public function getLess($urls)
    {
        $data = $this->content;

        $globalName = basename($data[1]['path']);
        $css = "/* This file was generated automatically. */\n\n";
        foreach ($data[1]['map'] as $name => $rect) {
            $retinaRules = '';
            if ($this->hasRetina) {
                $retinaX = $data[2]['map'][$name]['x'] / 2;
                $retinaY = $data[2]['map'][$name]['y'] / 2;
                $retinaBgWidth = $data[2]['width'] / 2;
                $retinaBgHeight = $data[2]['height'] / 2;
                $retinaRules = <<<EOF
@media only screen and (-webkit-min-device-pixel-ratio: 1.5),
       only screen and (min-resolution: 144dpi) {
    background: url({$urls[2]}) -{$retinaX}px -{$retinaY}px;
    background-size: {$retinaBgWidth}px {$retinaBgHeight}px;
}
EOF;
            }

            $name = str_replace('.png', '', $name);
            $x = $rect['x'];
            $y = $rect['y'];
            $width = $rect['w'];
            $height = $rect['h'];

            $spriteName = "sprite-{$globalName}-$name";

            $css .= <<<EOF
// @deprecated
.sprite_{$globalName}_$name() {
  background: url({$urls[1]}) -{$x}px -{$y}px;
  width: {$width}px;
  height: {$height}px;
}

.$spriteName() {
  background: url({$urls[1]}) -{$x}px -{$y}px;
  width: {$width}px;
  height: {$height}px;
  $retinaRules
}

.sprite-$globalName(@name) when (@name = $name) {
  background: url({$urls[1]}) -{$x}px -{$y}px;
  width: {$width}px;
  height: {$height}px;
  $retinaRules
}
EOF;
        }

        return $css;
    }

    private function loadConfig()
    {
        $configPath = $this->path . '/_sprite.json';
        if (is_file($configPath)) {
            $this->addSrcFile($configPath);
            $opts = json_decode(file_get_contents($configPath), true);
            $this->config = new Config($opts);
        } else {
            $this->config = new Config();
        }
    }

    private function load()
    {
        $this->addSrcFile($this->path);

        $images = [];

        foreach (glob($this->path.'/*.png') as $filePath) {
            preg_match('/(.*?)(@2x)?\.png$/', basename($filePath), $matches);
            $imageName = $matches[1];
            $scale = empty($matches[2]) ? 1 : 2;
            $images[$imageName][$scale] = new ImageRect($imageName,
                imagecreatefrompng($filePath));

            $this->hasRetina = $this->hasRetina || $scale == 2;
        }

        // Check that all scales are available.
        if ($this->hasRetina) {
            /** @var ImageRect[] $image */
            foreach ($images as $name => $image) {
                $id = "{$this->path}::$name";
                if (empty($image[1]) || empty($image[2])) {
                    throw new \Exception("Either x1 or x2 image is not " .
                        "provided for sprite $id");
                }
                if ($image[1]->getWidth() * 2 != $image[2]->getWidth() ||
                    $image[1]->getHeight() * 2 != $image[2]->getHeight()) {

                    throw new \Exception("Retina image's sides must be " .
                        "exactly twice as big in sprite $id");
                }
                if ($image[2]->getWidth() % 2 || $image[2]->getHeight() % 2) {
                    throw new \Exception("Both width and height of retina " .
                        "image must be dividable by 2 in sprite $id");
                }
            }
            $this->arrange($images, 2);
        }

        $this->arrange($images, 1);
    }

    private function arrange($images, $scale) {
        /** @var ImageRect[] $images */
        $images = array_map(function($el) use ($scale) {
            return $el[$scale];
        }, $images);

        $arranger = new Arranger();
        $size = $arranger->arrangeImages($images);

        $image = imagecreatetruecolor($size['width'], $size['height']);
        imagefill($image, 0, 0, imagecolortransparent($image));
        imagesavealpha($image, true);
        foreach ($images as $rect) {
            imagecopy($image, $rect->getImage(), $rect->x, $rect->y, 0, 0,
                $rect->getWidth(), $rect->getHeight());
        }

        ob_start();
        if ($this->config->getType($scale) == 'jpeg') {
            imagejpeg($image, null, $this->config->getQuality($scale));
        } else {
            imagepng($image);
        }
        $imgBlob = ob_get_clean();

        $content = array(
            'path' => $this->path,
            'img' => $imgBlob,
            'map' => $images,
            'width' => imagesx($image),
            'height' => imagesy($image),
        );
        $content['map'] = array_map(function(ImageRect $rect) {
            return array(
                'x' => $rect->x,
                'y' => $rect->y,
                'w' => $rect->getWidth(),
                'h' => $rect->getHeight(),
            );
        }, $content['map']);

        $this->content[$scale] = $content;
    }
}
