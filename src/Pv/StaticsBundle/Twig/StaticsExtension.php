<?php

namespace Pv\StaticsBundle\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsExtension extends Twig_Extension
{
    private $conatiner;
    private $debug;

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
            'statics_js' => new Twig_Function_Method($this, "jsFunc", array('is_safe' => array('html'))),
            'statics_css' => new Twig_Function_Method($this, "cssFunc", array('is_safe' => array('html'))),
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
        return $this->conatiner->get('statics.url_helper')->addVars($path, true);
    }
}
