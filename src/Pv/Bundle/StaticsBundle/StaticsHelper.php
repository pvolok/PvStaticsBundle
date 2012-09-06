<?php

namespace Pv\Bundle\StaticsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class StaticsHelper
{
    private $container;
    private $debug;

    function __construct(ContainerInterface $container, $debug)
    {
        $this->container = $container;
        $this->debug = $debug;
    }

    function path($path)
    {
        $locale = $this->conatiner->get('request')->getLocale();
        $path = preg_replace('/\.js$/', ".$locale.js", $path);
        if ($this->debug) {
            return "/s/$path";
        } else {
            if (!$this->namesMap) {
                $this->namesMap = include $this->conatiner->getParameter('kernel.root_dir').'/statics_map.php';
            }
            return (isset($this->namesMap[$path]) ? $this->namesMap[$path] : '/');
        }
    }
}
