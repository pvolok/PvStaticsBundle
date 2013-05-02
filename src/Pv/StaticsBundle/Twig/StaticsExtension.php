<?php

namespace Pv\StaticsBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsExtension extends \Twig_Extension
{
    private $container;
    private $debug;

    function __construct(ContainerInterface $container, $debug)
    {
        $this->container = $container;
        $this->debug = $debug;
    }

    function getName()
    {
        return 'statics';
    }

    function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('statics_js', array($this, 'jsFunc'),
                array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('statics_css', array($this, 'cssFunc'),
                array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('statics_path', array($this, 'pathFunc'),
                array('is_safe' => array('html'))),
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
        return $this->container->get('statics.url_helper')
            ->getUrl($path, $this->container->getParameter('statics.debug'));
    }
}
