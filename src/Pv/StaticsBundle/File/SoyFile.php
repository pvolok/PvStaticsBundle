<?php

namespace Pv\StaticsBundle\File;

use Symfony\Component\Process\ProcessBuilder;

class SoyFile extends BaseFile
{
    function load()
    {
        parent::load();

        $soy_jar = $this->container->getParameter('kernel.root_dir').'/../../tools/soy-20111222.jar';

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb->add('java')->add('-jar')->add($soy_jar);
        $pb->add('--shouldGenerateGoogMsgDefs');
        $pb->add('--bidiGlobalDir')->add('1');

        $tmp_out_file = tempnam('/tmp', 'statics');
        $pb->add('--outputPathFormat')->add($tmp_out_file);

        $tmp_file = tempnam('/tmp', 'statics');
        file_put_contents($tmp_file, $this->content);
        $pb->add($tmp_file);

        $proc = $pb->getProcess();
        if ($proc->run() != 0) {
            $this->content = $proc->getErrorOutput();
        } else {
            $this->content = file_get_contents($tmp_out_file);
        }

        unlink($tmp_file);
        unlink($tmp_out_file);
    }
}
