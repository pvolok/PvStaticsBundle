<?php

namespace Pv\Bundle\StaticsBundle\File;

class ImageFile extends BaseFile
{
    function getDataUrl()
    {
        $image = imagecreatefromstring($this->content);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image);
        $data = 'data:image/png;base64,'.base64_encode(ob_get_clean());
        return $data;
    }
}
