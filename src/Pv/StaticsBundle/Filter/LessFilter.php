<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\FileAsset;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class LessFilter
{
    public function filter(FileAsset $asset)
    {
        $content = $asset->getContent();

        $pb = ProcessBuilder::create()
            ->add('lessc')
            ->add('-');

        if (!$asset->getParam('debug')) {
            $pb->add('--compress');
        }

        $proc = new Process($pb->getProcess()->getCommandLine()); // TODO: fix
        $proc->setStdin($content);
        $proc->run();

        if (!$proc->isSuccessful()) {
            throw new \Exception($proc->getErrorOutput());
        }

        $content = $proc->getOutput();

        $asset->setContent($content);
    }
}
