<?php

namespace Pv\Bundle\StaticsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DevController extends Controller
{
    function renderAction($path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $this->getRequest()->setFormat('less', 'text/css');
        $this->getRequest()->setRequestFormat($ext);

        $content = $this->get('statics.manager')
            ->getFileContent($path, $this->getRequest()->getLocale(), true);
        if ($content) {
            return new Response($content);
        }
        return new Response('File not found.');
    }

}
