<?php

namespace Pv\StaticsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SpritesUpdateCommand  extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('sprites:update')
                ->setDescription('Regenerate all sprites.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            $public_dir = $bundle->getPath().'/Resources/statics';
            $dir = $public_dir.'/_sprites';
            if (is_dir($dir)) {
                foreach (glob($dir.'/*', GLOB_ONLYDIR) as $sprite_dir) {
                    $sprite_name = basename($sprite_dir);
                    $sprites_file = new SpritesFile($sprite_dir);
                    $sprites_file->compile();
                    imagepng($sprites_file->drawImage(), $public_dir.'/img/sprites/'.$sprite_name.'.png');
                    file_put_contents($public_dir.'/css/_sprites/'.$sprite_name.'.less', $sprites_file->generateLess());
                }
            }
        }
    }
}

class SpritesFile
{

    private $path;

    private $name;
    private $images = array();
    private $size;

    function __construct($path)
    {
        $this->path = $path;
    }

    function compile()
    {
        $this->name = basename($this->path);
        foreach (glob($this->path.'/*.png') as $file_path) {
            $image_name = basename($file_path);
            $this->images[$image_name] = new Sprites_ImageRect($image_name,
                imagecreatefrompng($file_path));
        }
        $sprites_arranger = new Sprites_Arranger();
        $this->size = $sprites_arranger->arrangeImages($this->images);
    }

    function drawImage()
    {
        $size = $this->size;
        $image = imagecreatetruecolor($size['width'], $size['height']);
        imagefill($image, 0, 0, imagecolortransparent($image));
        imagesavealpha($image, true);
        foreach ($this->images as $rect) {
            imagecopy($image, $rect->getImage(), $rect->x, $rect->y, 0, 0,
                $rect->getWidth(), $rect->getHeight());
        }

        return $image;
    }

    function generateLess()
    {
        $css = "/* This file was generated automatically. */\n\n";
        foreach ($this->images as $name => $image) {
            $name = str_replace('.png', '', $name);
            $x = $image->x;
            $y = $image->y;
            $width = $image->getWidth();
            $height = $image->getHeight();

            $css .= ".sprite_{$this->name}_$name() {\n";
            $css .= "  background: url(../img/sprites/{$this->name}.png) -{$x}px -{$y}px;\n";
            $css .= "  width: {$width}px;\n";
            $css .= "  height: {$height}px;\n";
            $css .= "}\n\n";
        }
        return $css;
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

    static function descHeightComparator($a, $b)
    {
        $c = $b->getHeight() - $a->getHeight();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    static function descWidthComparator($a, $b)
    {
        $c = $b->getWidth() - $a->getWidth();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    function arrangeImages($rects)
    {
        $rectsOrderedByHeight = $rects;
        usort($rectsOrderedByHeight, __NAMESPACE__.'\Sprites_Arranger::descHeightComparator');

        $rectsOrderedByWidth = $rects;
        usort($rectsOrderedByWidth, __NAMESPACE__.'\Sprites_Arranger::descWidthComparator');

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