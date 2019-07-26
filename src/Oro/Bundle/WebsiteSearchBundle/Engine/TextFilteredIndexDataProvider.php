<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Data provider for website search index data, used by ORM engine to filter all HTML tags
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
        if (is_array($value)) {
            foreach ($value as $key => $element) {
                $value[$key] = $this->clearValue($type, $fieldName, $element);
            }
            return $value;
        }

        if ($type === Query::TYPE_TEXT) {
            $value = $this->htmlTagHelper->stripTags((string)$value);
            $value = $this->htmlTagHelper->stripLongWords($value);
        }

        return $value;
    }
}
