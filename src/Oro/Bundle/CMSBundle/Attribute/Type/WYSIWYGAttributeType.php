<?php

namespace Oro\Bundle\CMSBundle\Attribute\Type;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Attribute type provides metadata about WYSIWYG attribute.
 */
class WYSIWYGAttributeType implements AttributeTypeInterface
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return WYSIWYGType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->htmlTagHelper->stripTags((string)$originalValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }
}
