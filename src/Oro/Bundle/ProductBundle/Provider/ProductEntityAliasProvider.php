<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides aliases for target classes of enum and multi-enum product attributes created by a user.
 */
class ProductEntityAliasProvider implements EntityAliasProviderInterface
{
    private const string PRODUCT_ATTR_ENUM_CLASS_NAME_PREFIX = ExtendHelper::ENUM_CLASS_NAME_PREFIX . 'Product_';

    private ConfigManager $configManager;
    private DuplicateEntityAliasResolver $duplicateResolver;
    private Inflector $inflector;
    /** @var array|null [class name => TRUE, ...] */
    private ?array $classes = null;

    public function __construct(
        ConfigManager $configManager,
        DuplicateEntityAliasResolver $duplicateResolver,
        Inflector $inflector
    ) {
        $this->configManager = $configManager;
        $this->duplicateResolver = $duplicateResolver;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (!str_starts_with($entityClass, self::PRODUCT_ATTR_ENUM_CLASS_NAME_PREFIX)) {
            return null;
        }

        $this->ensureInitialized();
        if (!isset($this->classes[$entityClass])) {
            return null;
        }

        $entityAlias = $this->duplicateResolver->getAlias($entityClass);
        if (null === $entityAlias) {
            $alias = $this->buildAlias($entityClass);
            $pluralAlias = $this->inflector->pluralize($alias);
            while ($this->duplicateResolver->hasAlias($alias, $pluralAlias)) {
                $alias = $this->duplicateResolver->getUniqueAlias($alias, $pluralAlias);
                $pluralAlias = $this->inflector->pluralize($alias);
            }
            $entityAlias = new EntityAlias($alias, $pluralAlias);
            $this->duplicateResolver->saveAlias($entityClass, $entityAlias);
        }

        return $entityAlias;
    }

    private function buildAlias(string $entityClass): string
    {
        // remove the product attribute enum class name prefix
        // ex: New_Attribute_8fde6396
        $attributeName = substr($entityClass, \strlen(self::PRODUCT_ATTR_ENUM_CLASS_NAME_PREFIX));
        // remove the hash from the class name we get more readable class name
        // ex: New_Attribute
        $attributeName = substr($attributeName, 0, strrpos($attributeName, '_'));
        // remove underscores and convert to lower case
        // ex: newattribute
        $attributeName = $attributeName && $attributeName !== '_'
            ? strtolower(str_replace('_', '', $attributeName))
            : 'attribute';

        return 'extproductattribute' . $attributeName;
    }

    private function ensureInitialized(): void
    {
        if (null !== $this->classes) {
            return;
        }

        $this->classes = [];
        $fields = $this->configManager->getConfigs('attribute', Product::class, true);
        foreach ($fields as $field) {
            if (!$field->is('is_attribute')) {
                continue;
            }
            if (!ExtendHelper::isEnumerableType($field->getId()->getFieldType())) {
                continue;
            }

            $enumCode = $this->configManager
                ->getFieldConfig('enum', Product::class, $field->getId()->getFieldName())
                ->get('enum_code');
            if (!$enumCode) {
                continue;
            }

            $enumOptionEntityClass = ExtendHelper::getOutdatedEnumOptionClassName($enumCode);
            if (!str_starts_with($enumOptionEntityClass, self::PRODUCT_ATTR_ENUM_CLASS_NAME_PREFIX)) {
                continue;
            }

            $this->classes[$enumOptionEntityClass] = true;
        }
    }
}
