<?php

namespace Pv\StaticsBundle\File;

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
        $less = new \lessc;

        if (!$this->debug) {
            $less->setFormatter('compressed');
        }

        $this->content = $less->compile($this->getContent());
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
