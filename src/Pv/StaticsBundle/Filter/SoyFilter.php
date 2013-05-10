<?php

namespace Pv\StaticsBundle\Filter;

use Pv\StaticsBundle\Asset\BaseAsset;
use Symfony\Component\Process\ProcessBuilder;

class SoyFilter
{
    private $soyJar;

    public function __construct($soyJar)
    {
        $this->soyJar = $soyJar;
    }

    public function filter(BaseAsset $asset)
    {
        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb->add('java')->add('-jar')->add($this->soyJar);
        $pb->add('--shouldGenerateGoogMsgDefs');
        $pb->add('--bidiGlobalDir')->add('1');

        $tmp_out_file = tempnam('/tmp', 'statics');
        $pb->add('--outputPathFormat')->add($tmp_out_file);

        $tmp_file = tempnam('/tmp', 'statics');
        file_put_contents($tmp_file, $asset->getContent());
        $pb->add($tmp_file);

        $proc = $pb->getProcess();
        if ($proc->run() != 0) {
            throw new \Exception('Soy exception: '.$proc->getErrorOutput());
        } else {
            $asset->setContent(file_get_contents($tmp_out_file));
        }

        unlink($tmp_file);
        unlink($tmp_out_file);
    }
}
