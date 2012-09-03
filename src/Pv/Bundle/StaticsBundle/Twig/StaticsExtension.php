<?php

namespace Pv\Bundle\StaticsBundle\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsExtension extends Twig_Extension
{
    private $conatiner;
    private $debug;

    private $namesMap;

    function __construct(ContainerInterface $container, $debug)
    {
        $this->conatiner = $container;
        $this->debug = $debug;
    }

    function getName()
    {
        return 'statics';
    }

    function getFunctions()
    {
        return array(
            'js' => new Twig_Function_Method($this, "jsFunc", array('is_safe' => array('html'))),
            'css' => new Twig_Function_Method($this, "cssFunc", array('is_safe' => array('html'))),
            'statics_path' => new Twig_Function_Method($this, "pathFunc", array('is_safe' => array('html')))
        );
    }

    function jsFunc($path)
    {
        $path = $this->pathFunc($path);
        return "<script src='$path'></script>";
    }

    function cssFunc($path)
    {
        $path = $this->pathFunc($path);
        return "<link rel='stylesheet' href='$path'>";
    }

    function pathFunc($path)
    {
        $locale = $this->conatiner->get('request')->getLocale();
        $path = preg_replace('/\.js$/', ".$locale.js", $path);
        if ($this->debug) {
            return "/s/$path";
        } else {
            if (!$this->namesMap) {
                $this->namesMap = include $this->conatiner->getParameter('kernel.root_dir').'/statics_map.php';
            }
            return '/s/'.(isset($this->namesMap[$path]) ? $this->namesMap[$path] : '/');
        }
    }
}
