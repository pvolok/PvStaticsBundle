<?php

namespace Pv\StaticsBundle\Asset;

class SpriteAsset extends BaseAsset
{
    private $img;

    /** @var Sprites_ImageRect[] */
    private $images;

    function __construct($uri, $path)
    {
        parent::__construct($uri);

        $this->path = $path;

        $this->load();
    }

    static function getPng($data)
    {
        return base64_decode($data['img']);
    }

    static function getLess($data, $url)
    {
        $scale = substr($data['path'], -2) == 'X2' ? 2 : 1;
        $backgroundSizeCss = '';
        if ($scale > 1) {
            $bgWidth = $data['width'] / $scale;
            $bgHeight = $data['height'] / $scale;
            $backgroundSizeCss = "background-size: {$bgWidth}px {$bgHeight}px;";
        }

        $globalName = basename($data['path']);
        $css = "/* This file was generated automatically. */\n\n";
        foreach ($data['map'] as $name => $rect) {
            $name = str_replace('.png', '', $name);
            $x = $rect['x'] / $scale;
            $y = $rect['y'] / $scale;
            $width = $rect['w'] / $scale;
            $height = $rect['h'] / $scale;

            $spriteName = "sprite-{$globalName}-$name";

            $css .= <<<EOF
// @deprecated
.sprite_{$globalName}_$name() {
  background: url($url) -{$x}px -{$y}px;\n
  width: {$width}px;
  height: {$height}px;
}

.$spriteName() {
  background: url($url) -{$x}px -{$y}px;
  width: {$width}px;
  height: {$height}px;
  $backgroundSizeCss
}
EOF;
        }

        return $css;
    }

    private function load()
    {
        $this->addSrcFile($this->path);

        foreach (glob($this->path.'/*.png') as $file_path) {
            $image_name = basename($file_path);
            $this->images[$image_name] = new Sprites_ImageRect($image_name,
                imagecreatefrompng($file_path));
        }
        $sprites_arranger = new Sprites_Arranger();
        $size = $sprites_arranger->arrangeImages($this->images);

        $image = imagecreatetruecolor($size['width'], $size['height']);
        imagefill($image, 0, 0, imagecolortransparent($image));
        imagesavealpha($image, true);
        foreach ($this->images as $rect) {
            imagecopy($image, $rect->getImage(), $rect->x, $rect->y, 0, 0,
                $rect->getWidth(), $rect->getHeight());
        }

        ob_start();
        imagepng($image);
        $this->img = ob_get_clean();

        $content = array(
            'path' => $this->path,
            'img' => base64_encode($this->img),
            'map' => $this->images,
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
        $this->content = json_encode($content);
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