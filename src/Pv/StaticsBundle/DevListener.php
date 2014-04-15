<?php

namespace Pv\StaticsBundle;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class DevListener
{
    private $staticsManager;

    public function __construct(StaticsManager $staticsManager)
    {
        $this->staticsManager = $staticsManager;
    }

    public  function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (strpos($path, '/s/') !== 0) {
            return;
        }
        $path = substr($path, 3);

        $request->setFormat('less', 'text/css');
        $request->setFormat('png', 'image/png');
        $request->setFormat('jpg', 'image/jpeg');
        $request->setFormat('gif', 'image/gif');
        $request->setFormat('svg', 'image/svg+xml');

        $uri = $path.'?'.$request->getQueryString();
        $asset = $this->staticsManager->get($uri);
        $content = $asset->getContent();
        if ($content) {
            $request->setRequestFormat(pathinfo($path, PATHINFO_EXTENSION));
            $event->setResponse(new Response($content));
        } else {
            $event->setResponse(new Response('File not found.', 404));
        }
    }
}
