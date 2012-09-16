<?php

namespace Pv\Bundle\StaticsBundle\File;

use Symfony\Component\Process\ProcessBuilder;

class LessFile extends BaseFile
{
    function load()
    {
        parent::load();

        $this->content = preg_replace_callback('/data-url\((.*?)\)/',
            array($this, 'dataUrlCallback'), $this->content);
        $this->content = preg_replace_callback('/statics-url\((.*?)\)/',
            array($this, 'staticsUrlCallback'), $this->content);
        $this->content = preg_replace_callback('/^\@import [\'"](.*?)[\'"];/m',
            array($this, 'includeCallback'), $this->content);

        /*if (preg_match_all('/^\@import [\'"](.*?)[\'"];$/m', $this->content, $matches)) {
            $inc_file = $this->sm->create($matches[1], $this->path);
            if ($inc_file) {
                $this->subfiles[] = $inc_file;
            }
        }*/
    }

    function fullCompile()
    {
        parent::fullCompile();

        $this->compileLess();
    }

    protected function compileLess()
    {
        $format = <<<'EOF'
var less = require('less');
var sys  = require(process.binding('natives').util ? 'util' : 'sys');

new(less.Parser)(%s).parse(%s, function(e, tree) {
    if (e) {
        less.writeError(e);
        process.exit(2);
    }

    try {
        sys.print(tree.toCSS(%s));
    } catch (e) {
        less.writeError(e);
        process.exit(3);
    }
});

EOF;

        // parser options
        $parserOptions = array();
        //$parserOptions['paths'] = $this->paths;$this->container->get()
        $parserOptions['filename'] = basename($this->path);

        // tree options
        $treeOptions = array();
        $treeOptions['compress'] = !$this->debug;

        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();

        // node.js configuration
        $nodePaths = array('/usr/local/lib/node_modules');
        if (0 < count($nodePaths)) {
            $pb->setEnv('NODE_PATH', implode(':', $nodePaths));
        }

        $pb->add('node')->add($input = tempnam(sys_get_temp_dir(), 'statics_less'));
        file_put_contents($input, sprintf($format,
            json_encode($parserOptions),
            json_encode($this->content),
            json_encode($treeOptions)
        ));

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 < $code) {
            throw new \Exception($proc->getErrorOutput());
        }

        $this->content = $proc->getOutput();
    }

    protected function dataUrlCallback($matches)
    {
        if ($inc_file = $this->sm->create($matches[1], $this->params, $this)) {
        } else {
            throw new \Exception('Image ('.$matches[1].') not found for data-url.');
        }

        $this->subfiles[] = $inc_file;

        return 'url('.$inc_file->getDataUrl().')';
    }

    protected function staticsUrlCallback($matches)
    {
        $fileLocalPath = $matches[1];
        if ($inc_file = $this->sm->create($fileLocalPath, $this->params, $this)) {
        } else {
            throw new \Exception('Static file ('.$matches[1].') not found for statics-url.');
        }

        $this->subfiles[] = $inc_file;

        return 'url('.$this->sm->getUrl($fileLocalPath, $this->debug).')';
    }

    protected function includeCallback($matches)
    {
        if ($inc_file = $this->sm->create($matches[1], $this->params, $this)) {
        } else {
            return $matches[0];
        }

        $this->subfiles[] = $inc_file;

        if ($inc_file instanceof SpriteFile) {
            $content = $inc_file->generateLess();
        } else {
            $content = $inc_file->getContent();
        }

        return $content;
    }
}
