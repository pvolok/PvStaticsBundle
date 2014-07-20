<?php

namespace Pv\StaticsBundle\Filter;

class JsIncludeFilter extends IncludeFilter
{
    const REGEX = '/\/\/ #include (\'|")(?P<url>[^\'"\)\n\r]*)\1;?/';
    const DEPEND_REGEX = '/\/\/ #depend (\'|")(?P<url>[^\'"\)\n\r]*)\1;?/';

    protected function getRegex()
    {
        return self::REGEX;
    }

    protected function getRequireRegex()
    {
        return self::DEPEND_REGEX;
    }
}
