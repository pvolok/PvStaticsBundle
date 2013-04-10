<?php

namespace Pv\StaticsBundle\File;

use DateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pv\StaticsBundle\StaticsManager;

class BaseFile
{
    protected $container;
    protected $sm;

    protected $uri;
    protected $path;
    protected $params;
    protected $content;
    protected $subfiles = array();
    protected $debug = false;

    protected $srcFiles;

    protected $toolsDir;

    function __construct($uri, $path, ContainerInterface $container, StaticsManager $sm, $params)
    {
        $this->uri = $uri;
        $this->path = $path;
        $this->container = $container;
        $this->sm = $sm;
        $this->params = $params;

        if (isset($params['debug'])) {
            $this->debug = $params['debug'];
        }
    }

    function setToolsDir($dir)
    {
        $this->toolsDir = $dir;
    }

    function load()
    {
        /*if (null == $this->abs_path) {
            $this->abs_path = $this->resolvePath($this->path);
        }*/
        $this->content = file_get_contents($this->path);
    }

    /**
     * Is only called for top level files
     */
    function fullCompile()
    {
        //
    }

    function getPath()
    {
        return $this->path;
    }

    function getParams()
    {
        return $this->params;
    }

    function getContent()
    {
        return $this->content;
    }

    function getSrcFiles()
    {
        if (!$this->srcFiles) {
            $ret = array($this->path);
            foreach ($this->subfiles as $subfile) {
                $ret = array_merge($ret, $subfile->getSrcFiles());
            }
            $this->srcFiles = $ret;
        }

        return $this->srcFiles;
    }

    protected function resolvePath($path)
    {
        foreach ($this->paths as $base_path) {
            $abs_path = $base_path.'/'.$path;
            if (file_exists($abs_path)) {
                return $abs_path;
            }
        }
        return false;
    }

    /**
     * @param $path
     * @return array array('rel' => , 'abs' => )
     */
    protected function getSubfilePathInfo($path)
    {
        if (file_exists(dirname($this->abs_path).'/'.$path)) {
            return array(
                'rel' => dirname($this->path).'/'.$path,
                'abs' => dirname($this->abs_path).'/'.$path
            );
        }
        return array(
            'rel' => $path,
            'abs' => $this->resolvePath($path)
        );
    }

    function __toString()
    {
        return $this->getContent();
    }
}
