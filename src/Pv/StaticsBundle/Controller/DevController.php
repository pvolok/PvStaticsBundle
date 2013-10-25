<?php

namespace Pv\StaticsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DevController extends Controller
{
    function renderAction($path)
    {
        $this->getRequest()->setFormat('less', 'text/css');
        $this->getRequest()->setFormat('svg', 'image/svg+xml');

        $uri = $path.'?'.$this->getRequest()->getQueryString();
        $asset = $this->get('statics.manager')->get($uri);
        $content = $asset->getContent();
        if ($content) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $this->getRequest()->setRequestFormat($ext);
            return new Response($content);
        }
        return new Response('File not found.');
    }

}
