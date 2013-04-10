<?php

namespace Pv\StaticsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DevController extends Controller
{
    function renderAction($path)
    {
        $this->getRequest()->setFormat('less', 'text/css');

        $content = $this->get('statics.manager')
            ->getFileContent($path, $this->getRequest()->getLocale(), true);
        if ($content) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $this->getRequest()->setRequestFormat($ext);
            return new Response($content);
        }
        return new Response('File not found.');
    }

}
