<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\FileAsset;
use Pv\StaticsBundle\StaticsManager;

class LessphpFilter
{
    /**
     * @var \Pv\StaticsBundle\StaticsManager
     */
    private $manager;

    public function __construct(StaticsManager $manager)
    {
        $this->manager = $manager;
    }

    public function filter(FileAsset $asset)
    {
        $content = $asset->getContent();

        $less = new \lessc();

        $less->registerFunction('data_uri', function($arg) use($asset) {
            if ($arg[0] !== 'string') {
                throw new \Exception("The data_uri function accepts only strings ($arg[0] given).");
            }

            $path = $arg[2][0];
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'png') {
                throw new \Exception("Only png allowed in data_uri function ($path).");
            }

            $data = base64_encode($this->manager->load($path, $asset)->getContent());

            return "url('data:image/png;base64,$data')";
        });

        if (!$asset->getParam('debug')) {
            $less->setFormatter('compressed');
        }

        $content = $less->compile($content);

        $asset->setContent($content);
    }
}
