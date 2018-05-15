<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * TODO: Should be removed after https://magecore.atlassian.net/browse/BB-12955
 */
class TextFilteredIndexDataProvider extends IndexDataProvider
{
    /**
     * Clear HTML in text fields
     *
     * @param string $type
     * @param string $fieldName
     * @param mixed $value
     * @return mixed|string
     */
    protected function clearValue($type, $fieldName, $value)
    {
        if ($type === Query::TYPE_TEXT) {
            $value = $this->htmlTagHelper->stripTags((string)$value);
            $value = $this->htmlTagHelper->stripLongWords($value);
        }

        return $value;
    }
}
