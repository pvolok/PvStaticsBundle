<?php

namespace Pv\StaticsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UrlHelper
{
    protected $container;

    private $namesMap;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    function getUrl($url, $debug = false)
    {
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        if ($ext == 'js') {
            $locale = $this->container->get('request')->getLocale();
            $url .= "?locale=$locale";
        }

        if ($debug) {
            return "/s/$url";
        } else {
            if (!$this->namesMap) {
                $this->namesMap = include $this->conatiner->getParameter('kernel.root_dir').'/statics_map.php';
            }
            return (isset($this->namesMap[$url]) ? $this->namesMap[$url] : '/');
        }
    }
}