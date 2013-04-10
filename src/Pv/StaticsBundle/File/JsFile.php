<?php

namespace Pv\StaticsBundle\File;

use Symfony\Component\Process\ProcessBuilder;

class JsFile extends BaseFile
{
    function load()
    {
        parent::load();

        $this->content = preg_replace_callback('/^\/\/ #include \'(.*?)\';$/m',
            array($this, 'includeCallback'), $this->content);
    }

    function fullCompile()
    {
        parent::fullCompile();

        if ($this->debug) {
            return;
        }

        $compiler_jar = $this->container->getParameter('kernel.root_dir').'/../../tools/closure-20120917.jar';

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();
        $pb->add('java')->add('-jar')->add($compiler_jar);

        $pb->add('--charset')->add('UTF-8');
        $pb->add('--compilation_level')->add('SIMPLE_OPTIMIZATIONS');

        $tmp_file = tempnam('/tmp', 'statics');
        file_put_contents($tmp_file, $this->content);
        $pb->add('--js')->add($tmp_file);

        $proc = $pb->getProcess();
        if ($proc->run() != 0) {
            $this->content = $proc->getErrorOutput();
        } else {
            $this->content = $proc->getOutput();
        }

        unlink($tmp_file);
    }

    protected function includeCallback($matches)
    {
        if ($inc_file = $this->sm->create($matches[1], $this->params, $this)) {
        } else {
            return $matches[0];
        }

        $this->subfiles[] = $inc_file;

        return $inc_file->getContent();
    }
}
