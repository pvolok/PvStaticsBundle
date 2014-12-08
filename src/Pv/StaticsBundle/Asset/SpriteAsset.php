<?php

namespace Pv\StaticsBundle\Asset;

class SpriteAsset extends BaseAsset
{
    protected $hasRetina = false;

    function __construct($uri, $path)
    {
        parent::__construct($uri);

        $this->path = $path;
        $this->content = [];

        $this->load();
    }

    public function getScales()
    {
        return $this->hasRetina ? [1, 2] : [1];
    }

    public function getPng($scale)
    {
        return $this->content[$scale]['img'];
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

    private function load()
    {
        $this->addSrcFile($this->path);

        $images = [];

        foreach (glob($this->path.'/*.png') as $filePath) {
            preg_match('/(.*?)(@2x)?\.png$/', basename($filePath), $matches);
            $imageName = $matches[1];
            $scale = empty($matches[2]) ? 1 : 2;
            $images[$imageName][$scale] = new Sprites_ImageRect($imageName,
                imagecreatefrompng($filePath));

            $this->hasRetina = $this->hasRetina || $scale == 2;
        }

        // Check that all scales are available.
        if ($this->hasRetina) {
            /** @var Sprites_ImageRect[] $image */
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
        /** @var Sprites_ImageRect[] $images */
        $images = array_map(function($el) use ($scale) {
            return $el[$scale];
        }, $images);

        $arranger = new Sprites_Arranger();
        $size = $arranger->arrangeImages($images);

        $image = imagecreatetruecolor($size['width'], $size['height']);
        imagefill($image, 0, 0, imagecolortransparent($image));
        imagesavealpha($image, true);
        foreach ($images as $rect) {
            imagecopy($image, $rect->getImage(), $rect->x, $rect->y, 0, 0,
                $rect->getWidth(), $rect->getHeight());
        }

        ob_start();
        imagepng($image);
        $imgBlob = ob_get_clean();

        $content = array(
            'path' => $this->path,
            'img' => $imgBlob,
            'map' => $images,
            'width' => imagesx($image),
            'height' => imagesy($image),
        );
        $content['map'] = array_map(function(Sprites_ImageRect $rect) {
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


class Sprites_ImageRect
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


class Sprites_Arranger
{

    static function descHeightComparator(Sprites_ImageRect $a, Sprites_ImageRect $b)
    {
        $c = $b->getHeight() - $a->getHeight();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    static function descWidthComparator(Sprites_ImageRect $a, Sprites_ImageRect $b)
    {
        $c = $b->getWidth() - $a->getWidth();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    /**
     * @param Sprites_ImageRect[] $rects
     */
    function arrangeImages($rects)
    {
        $rectsOrderedByHeight = $rects;
        usort($rectsOrderedByHeight, self::class.'::descHeightComparator');

        $rectsOrderedByWidth = $rects;
        usort($rectsOrderedByWidth, self::class.'::descWidthComparator');

        $first = $rectsOrderedByHeight[0];
        $first->setPosition(0, 0);

        $curX = $first->getWidth();
        $colH = $first->getHeight();

        for ($i = 1, $n = count($rectsOrderedByHeight); $i < $n; ++$i) {
            if ($rectsOrderedByHeight[$i]->hasBeenPositioned) {
                continue;
            }

            $colW = 0;
            $curY = 0;

            $rectsInColumn = array();
            for ($j = $i; $j < $n; ++$j) {
                $current = $rectsOrderedByHeight[$j];
                if (!$current->hasBeenPositioned
                    && ($curY + $current->getHeight()) <= $colH
                ) {
                    $current->setPosition($curX, 0);
                    $colW = max($colW, $current->getWidth());
                    $curY += $current->getHeight();

                    $rectsInColumn[] = $current;
                }
            }

            if (count($rectsInColumn)) {
                $this->arrangeColumn($rectsInColumn, $rectsOrderedByWidth);
            }

            $curX += $colW;
        }

        return array(
            'width' => $curX,
            'height' => $colH
        );
    }

    /**
     * @param Sprites_ImageRect[] $rectsInColumn
     * @param Sprites_ImageRect[] $remainingRectsOrderedByWidth
     */
    private function arrangeColumn($rectsInColumn,
                                   $remainingRectsOrderedByWidth)
    {
        $first = $rectsInColumn[0];

        $columnWidth = $first->getWidth();
        $curY = $first->getHeight();

        for ($i = 1, $m = count($rectsInColumn); $i < $m; ++$i) {
            $r = $rectsInColumn[$i];
            $r->setPosition($r->x, $curY);
            $curX = $r->getWidth();

            for ($j = 0, $n = count($remainingRectsOrderedByWidth); $j < $n; ++$j) {
                $current = $remainingRectsOrderedByWidth[$j];
                if (!$current->hasBeenPositioned
                    && ($curX + $current->getWidth()) <= $columnWidth
                    && ($current->getHeight() <= $r->getHeight())
                ) {
                    $current->setPosition($r->x + $curX, $r->y);
                    $curX += $current->getWidth();
                }
            }

            $curY += $r->getHeight();
        }
    }

}
