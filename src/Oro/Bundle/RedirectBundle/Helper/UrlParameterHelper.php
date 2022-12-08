<?php

namespace Oro\Bundle\RedirectBundle\Helper;

/**
 * Helper class for URL parameters' manipulations
 */
class UrlParameterHelper
{
    /**
     * @param array|null $parameters
     *
     * @return string
     */
    public static function hashParams(array $parameters = null)
    {
        return md5(base64_encode(serialize($parameters)));
    }

    public static function normalizeNumericTypes(array &$data)
    {
        array_walk_recursive(
            $data,
            static function (&$value) {
                if (is_numeric($value) && (mb_strlen((string) $value) === mb_strlen((string) (0 + $value)))) {
                    // if a string and numeric, will return int or float
                    $value += 0;
                }
            }
        );
    }
}
