<?php

namespace Pv\StaticsBundle\Filter;

class CssIncludeFilter extends IncludeFilter
{
    const REGEX = '/@import (?:url\()?(\'|"|)(?P<url>[^\'"\)\n\r]*)\1\)?;?/';

    protected function getRegex()
    {
        return self::REGEX;
    }
}
