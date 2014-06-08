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

        $file = tmpfile();
        fwrite($file, $content);
        $filename = stream_get_meta_data($file)['uri'];

        $pb = ProcessBuilder::create()
            ->add('node')
            ->add('/usr/local/bin/lessc')
            ->add($filename);

        if (!$asset->getParam('debug')) {
            $pb->add('--compress');
        }

        $proc = $pb->getProcess();
        $proc->run();

        fclose($file);

        if (!$proc->isSuccessful()) {
            throw new \Exception($proc->getErrorOutput());
        }

        $content = $proc->getOutput();

        $asset->setContent($content);
    }
}
