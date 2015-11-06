<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer;

class LocaleCodeFormatter
{
    const DEFAULT_LOCALE = 'default';

    /**
     * @param mixed $code
     * @return string
     */
    public static function format($code)
    {
        if (!$code) {
            return self::DEFAULT_LOCALE;
        }

        return (string)$code;
    }
}
