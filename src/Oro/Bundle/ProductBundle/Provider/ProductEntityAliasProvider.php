<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides aliases for target classes for enum and multi-enum product attributes.
 */
class ProductEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var ConfigManager */
    private $configManager;

    /** @var DuplicateEntityAliasResolver */
    private $duplicateResolver;

    /** @var string */
    private $extendedProductPrefix;

    /** @var array [class name => TRUE, ...] */
    private $classes;

    /**
     * @param ConfigManager                $configManager
     * @param DuplicateEntityAliasResolver $duplicateResolver
     */
    public function __construct(ConfigManager $configManager, DuplicateEntityAliasResolver $duplicateResolver)
    {
        $this->configManager = $configManager;
        $this->duplicateResolver = $duplicateResolver;
        $this->extendedProductPrefix = ExtendHelper::ENTITY_NAMESPACE . 'EV_Product_';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        // quick check to exclude classes that are not related to Product attributes
        if (false === stripos($entityClass, $this->extendedProductPrefix)) {
            return null;
        }

        $this->ensureInitialized();
        if (!isset($this->classes[$entityClass])) {
            return null;
        }

        $entityAlias = $this->duplicateResolver->getAlias($entityClass);
        if (null === $entityAlias) {
            $entityAlias = $this->doGetEntityAlias($entityClass);
            $this->duplicateResolver->saveAlias($entityClass, $entityAlias);
        }

        return $entityAlias;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias
     */
    private function doGetEntityAlias(string $entityClass): EntityAlias
    {
        // remove namespace to get a short class name
        // ex: New_Attribute_8fde6396
        $shortEntityClass = substr($entityClass, strlen($this->extendedProductPrefix));
        // remove the hash from the class name we get more readable class name
        // ex: New_Attribute
        $cleanEntityClass = substr($shortEntityClass, 0, strrpos($shortEntityClass, '_'));

        $alias = $this->buildAlias($cleanEntityClass);
        $pluralAlias = Inflector::pluralize($alias);
        if ($this->duplicateResolver->hasAlias($alias, $pluralAlias)) {
            $alias = $this->duplicateResolver->getUniqueAlias($alias, $pluralAlias);
            $pluralAlias = $alias;
        }

        return new EntityAlias($alias, $pluralAlias);
    }

    /**
     * @param string $className The class name without namespace
     *
     * @return string
     */
    private function buildAlias(string $className): string
    {
        return 'product' . str_replace('_', '', strtolower($className));
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

            $targetEntityClass = $this->configManager
                ->getFieldConfig('extend', Product::class, $field->getId()->getFieldName())
                ->get('target_entity');
            if (!$targetEntityClass
                || !$this->configManager->getEntityConfig('enum', $targetEntityClass)->get('code')
            ) {
                continue;
            }

            $this->classes[$targetEntityClass] = true;
        }
    }
}
