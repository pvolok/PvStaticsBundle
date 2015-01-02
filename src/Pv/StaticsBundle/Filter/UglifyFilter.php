<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\BaseAsset;
use Symfony\Component\Process\ProcessBuilder;

class UglifyFilter
{
    public function filter(BaseAsset $asset)
    {
        if ($asset->getParam('debug')) {
            return;
        }

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb->add('uglifyjs');
        $pb->addEnvironmentVariables([
            'PATH' => '/opt/local/bin' // MacPorts bin path.
        ]);

        $pb->add('-c');
        $pb->add('-m');

        $pb->setInput($asset->getContent());

        $proc = $pb->getProcess();
        if ($proc->run() != 0) {
            throw new \Exception('Error in uglifyjs: '
                . $proc->getErrorOutput());
        } else {
            $asset->setContent($proc->getOutput());
        }
    }
}
