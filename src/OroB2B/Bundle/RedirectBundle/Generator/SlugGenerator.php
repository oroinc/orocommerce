<?php

namespace OroB2B\Bundle\RedirectBundle\Generator;

class SlugGenerator
{
    public function slugify($string)
    {
        $string = transliterator_transliterate(
            "Any-Latin;
            NFD;
            [:Nonspacing Mark:] Remove;
            [^\u0000-\u007E] Remove;
            NFC;
            [:Punctuation:] Remove;
            Lower();",
            $string
        );
        $string = preg_replace('/[-\s]+/', '-', $string);
        return trim($string, '-');
    }
}
