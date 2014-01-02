<?php

namespace Pv\StaticsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DevController extends Controller
{
    function renderAction(Request $request, $path)
    {
        $request->setFormat('less', 'text/css');
        $request->setFormat('svg', 'image/svg+xml');

        $uri = $path.'?'.$request->getQueryString();
        $asset = $this->get('statics.manager')->get($uri);
        $content = $asset->getContent();
        if ($content) {
            $request->setRequestFormat(pathinfo($path, PATHINFO_EXTENSION));
            return new Response($content);
        }
        return new Response('File not found.');
    }

}
