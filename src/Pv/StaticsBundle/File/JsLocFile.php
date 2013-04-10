<?php

namespace Pv\StaticsBundle\File;

use Symfony\Component\DependencyInjection\Container;

class JsLocFile extends BaseFile
{
    function load()
    {
        parent::load();

        global $kernel;
        /** @var $t \Symfony\Bundle\FrameworkBundle\Translation\Translator */
        $t = $this->container->get('translator');
        $data = json_decode($this->content, true);

        $_id = $data['_id'];
        $domain = isset($data['_domain']) ? $data['_domain'] : 'messages';
        unset($data['_id']);
        unset($data['_domain']);

        $translated = array();
        foreach ($data as $js_key => $id) {
            $translated[$js_key] = $t->trans($id ?: $js_key, array(), $domain, $this->params['locale']);
        }

        $this->content = 'window.MSGS_'.$_id.'='.json_encode($translated).';';
    }
}
