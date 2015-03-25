<?php

namespace OroB2B\Bundle\RedirectBundle\Generator;

class SlugGenerator
{
    public function slugify($string)
    {
        $string = transliterator_transliterate(
            "Any-Latin;
            Latin-ASCII;
            NFD;
            [:Nonspacing Mark:] Remove;
            [^\u0020\u002D\u0030-\u0039\u0041-\u005A\u0041-\u005A\u005F\u0061-\u007A\u007E] Remove;
            NFC;
            Lower();",
            $string
        );
        $string = preg_replace('/[-\s]+/', '-', $string);
        return trim($string, '-');
    }
}
