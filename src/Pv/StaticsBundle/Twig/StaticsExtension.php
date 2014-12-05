<?php

namespace Pv\StaticsBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsExtension extends \Twig_Extension
{
    private $container;
    private $debug;
    private $closureMap;

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
        return [
            new \Twig_SimpleFunction('statics_js', [$this, 'jsFunc'],
                ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('statics_css', [$this, 'cssFunc'],
                ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('statics_path', [$this, 'pathFunc'],
                ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('closure_init',[$this, 'closureInitFunc'],
                ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('closure_require',
                [$this, 'closureRequireFunc'], ['is_safe' => ['html']]),
        ];
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

    function closureInitFunc($deps)
    {
        $debug = $this->container->getParameter('statics.debug');
        $html = '';
        if ($debug) {
            $html .= '<script src="/c/bower_components/' .
                'closure-library/closure/goog/base.js"></script>';
            foreach ($deps as $oneDeps) {
                $html .= "<script src='/c/$oneDeps'></script>";
            }
        }
        return $html;
    }

    function closureRequireFunc($namespace)
    {
        $debug = $this->container->getParameter('statics.debug');
        if ($debug) {
            $html = "<script>goog.require('$namespace');</script>";
        } else {
            $map = $this->getClosureMap();
            $html = "<script src='/c/{$map[$namespace]}'></script>";
        }
        return $html;
    }

    private function getClosureMap()
    {
        if (!$this->closureMap) {
            $path = $this->container->getParameter('kernel.root_dir') .
                '/../js/compiled_map.json';
            $this->closureMap = json_decode(file_get_contents($path), true);
        }
        return $this->closureMap;
    }
}
