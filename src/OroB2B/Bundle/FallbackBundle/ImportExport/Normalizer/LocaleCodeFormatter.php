<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleCodeFormatter
{
    const DEFAULT_LOCALE = 'default';

    /**
     * @param Locale|string $locale
     * @return string
     */
    public static function format($locale = null)
    {
        if (!$locale) {
            return self::DEFAULT_LOCALE;
        }

        if ($locale instanceof Locale) {
            $code = $locale->getCode();
            if (!$code) {
                return self::DEFAULT_LOCALE;
            }

            return (string)$code;
        }

        return (string)$locale;
    }
}
