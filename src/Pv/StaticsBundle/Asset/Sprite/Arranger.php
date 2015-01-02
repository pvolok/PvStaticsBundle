<?php

namespace Pv\StaticsBundle\Asset\Sprite;

class Arranger
{
    static function descHeightComparator(ImageRect $a, ImageRect $b)
    {
        $c = $b->getHeight() - $a->getHeight();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    static function descWidthComparator(ImageRect $a, ImageRect $b)
    {
        $c = $b->getWidth() - $a->getWidth();
        return ($c != 0) ? $c : strcmp($b->getName(), $a->getName());
    }

    /**
     * @param ImageRect[] $rects
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
     * @param ImageRect[] $rectsInColumn
     * @param ImageRect[] $remainingRectsOrderedByWidth
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
