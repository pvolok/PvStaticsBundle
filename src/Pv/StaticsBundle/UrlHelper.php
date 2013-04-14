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
        $query = http_build_query($this->getValues($this->getVars($url)));
        $url = $url.'?'.$query;

        if ($debug) {
            return "/s/$url";
        } else {
            if (!$this->namesMap) {
                $this->namesMap = include $this->conatiner->getParameter('kernel.root_dir').'/statics_map.php';
            }
            return (isset($this->namesMap[$url]) ? $this->namesMap[$url] : '/');
        }
    }

    function getUrlVariants($url)
    {
        $variants = $this->container->getParameter('statics.vars');

        $vars = $this->getVars($url);
        $arr = null;
        foreach ($vars as $var) {
            $newArr = array();
            foreach ($variants[$var] as $val) {
                if ($arr) {
                    foreach ($arr as $el) {
                        $el[$var] = $val;
                        $newArr[] = $el;
                    }
                } else {
                    $newArr[] = array($var => $val);
                }
            }
            $arr = $newArr ?: $arr;
        }

        if ($arr) {
            return array_map(function($el) use ($url) {
                return $url.'?'.http_build_query($el);
            }, $arr);
        } else {
            return array("$url?");
        }
    }

    protected function getVars($uri)
    {
        $vars = array();
        $ext = pathinfo($uri, PATHINFO_EXTENSION);

        if ($ext === 'js') {
            $vars[] = 'locale';
        }

        return $vars;
    }

    protected function getValues($vars) {
        $ret = array();

        foreach ($vars as $var) {
            switch ($var) {
                case 'locale':
                    $val = $this->container->get('request')->getLocale();
                    break;
                default:
                    $val = null;
                    break;
            }
            $ret[$var] = $val;
        }

        return $ret;
    }
}