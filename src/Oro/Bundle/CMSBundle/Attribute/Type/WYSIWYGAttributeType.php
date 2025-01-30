<?php

namespace Oro\Bundle\CMSBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Provides metadata about WYSIWYG attribute type.
 */
class WYSIWYGAttributeType implements AttributeTypeInterface
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    #[\Override]
    public function isSearchable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function isFilterable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function isSortable(FieldConfigModel $attribute)
    {
        return false;
    }

    #[\Override]
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        return $this->htmlTagHelper->stripTags((string)$originalValue);
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }
}
