<?php

namespace Pv\Bundle\StaticsBundle\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;

class StaticsExtension extends Twig_Extension
{
    private $conatiner;

    function __construct(Kernel $kernel)
    {
        $this->conatiner = $kernel->getContainer();
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
        $locale = $this->conatiner->get('request')->getLocale();
        //$path = preg_replace('/\.js$/', '_'.$locale.'.js', $path);
        return "<script src='/s/$path'></script>";
    }

    function cssFunc($path)
    {
        return "<link rel='stylesheet' href='/s/$path'>";
    }

    function pathFunc($path)
    {
        $locale = $this->conatiner->get('request')->getLocale();
        //$path = preg_replace('/\.js$/', '_'.$locale.'.js', $path);
        return "/s/$path";
    }
}
