<?php

namespace Pv\StaticsBundle\Filter;


use Pv\StaticsBundle\Asset\BaseAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\ProcessBuilder;

class ClosureCompilerFilter
{
    private $compilerJar;

    public function __construct($compilerJar)
    {
        $this->compilerJar = $compilerJar;
    }

    public function filter(BaseAsset $asset)
    {
        if ($asset->getParam('debug')) {
            return;
        }

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb->add('java')->add('-jar')->add($this->compilerJar);

        $pb->add('--charset')->add('UTF-8');
        $pb->add('--compilation_level')->add('SIMPLE_OPTIMIZATIONS');

        $tmp = tmpfile();
        $tmpPath = stream_get_meta_data($tmp)['uri'];
        file_put_contents($tmpPath, $asset->getContent());
        $pb->add('--js')->add($tmpPath);

        $proc = $pb->getProcess();
        if ($proc->run() != 0) {
            throw new \Exception('Error in the closure compiler: '
                .$proc->getErrorOutput());
        } else {
            $asset->setContent($proc->getOutput());
        }
    }
}
