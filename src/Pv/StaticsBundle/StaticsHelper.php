<?php

namespace Pv\StaticsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsHelper
{
    private $container;
    private $debug;

    private $namesMap;

    function __construct(ContainerInterface $container, $debug)
    {
        $this->container = $container;
        $this->debug = $debug;
    }

    function path($path)
    {
        $locale = $this->container->get('request')->getLocale();
        $path = preg_replace('/\.js$/', ".$locale.js", $path);
        if ($this->debug) {
            return "/s/$path";
        } else {
            if (!$this->namesMap) {
                $this->namesMap = include $this->container->getParameter('kernel.root_dir').'/statics_map.php';
            }
            return (isset($this->namesMap[$path]) ? $this->namesMap[$path] : '/');
        }
    }
}
