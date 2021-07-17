<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToOneSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\MultiEnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\OneToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\TextSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\WYSIWYGSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Provides searchable info for attribute.
 */
class SearchableInformationProvider
{
    public const SEARCHABLE_PREFIX = 'searchable';

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getSearchableFieldName(
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType
    ): string {
        switch (get_class($attributeType)) {
            case EnumSearchableAttributeType::class:
            case MultiEnumSearchableAttributeType::class:
                return $attribute->getFieldName() . '_' . self::SEARCHABLE_PREFIX;
            case ManyToOneSearchableAttributeType::class:
            case OneToManySearchableAttributeType::class:
            case ManyToManySearchableAttributeType::class:
                return $attribute->getFieldName() . '_' . LocalizationIdPlaceholder::NAME;
            case TextSearchableAttributeType::class:
            case StringSearchableAttributeType::class:
            case WYSIWYGSearchableAttributeType::class:
                return $attribute->getFieldName();
        }

        throw new \LogicException(sprintf('Type %s is not supported', get_class($attributeType)));
    }

    public function getAttributeSearchBoost(FieldConfigModel $attribute): ?float
    {
        $className = $attribute->getEntity()->getClassName();
        $fieldName = $attribute->getFieldName();

        return $this->configManager->getProvider('attribute')
            ->getConfig($className, $fieldName)
            ->get('search_boost');
    }
}
