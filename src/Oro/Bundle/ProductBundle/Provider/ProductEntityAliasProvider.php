<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var AttributeConfigHelper */
    private $attributeConfigHelper;

    /** @var ConfigManager */
    private $configManager;

    /** @var string */
    private $extendedProductPrefix;

    /**
     * @param AttributeConfigHelper $attributeConfigHelper
     * @param ConfigManager $configManager
     */
    public function __construct(AttributeConfigHelper $attributeConfigHelper, ConfigManager $configManager)
    {
        $this->attributeConfigHelper = $attributeConfigHelper;
        $this->configManager = $configManager;
        $this->extendedProductPrefix = ExtendHelper::ENTITY_NAMESPACE . 'EV_Product_';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        // Quick check to exclude classes that are not Product extended related
        if (false === stripos($entityClass, $this->extendedProductPrefix)) {
            return null;
        }

        // Product attributes are dictionary classes, we have to remove the hash from the class name in API
        // See #BB-10758
        $fieldName = $this->getAliasFromEntityClass($entityClass);
        if ($this->attributeConfigHelper->isFieldAttribute(Product::class, $fieldName)) {
            list($alias, $plural) = $this->getEntityAliasAndPlural($fieldName);

            return new EntityAlias($alias, $plural);
        }

        return null;
    }

    /**
     * @param string $entityClass ex:Extend\Entity\EV_Product_New_Attribute_8fde6396
     * @return string
     */
    protected function getAliasFromEntityClass($entityClass)
    {
        // starting with a FQN like Extend\Entity\EV_Product_New_Attribute_8fde6396 we get a clean entity class name
        // ex: New_Attribute_8fde6396
        $cleanEntityClass = str_replace($this->extendedProductPrefix, '', $entityClass);

        // and if we remove the hash from the class name we get the field name
        // ex: New_Attribute
        $lastPos = strrpos($cleanEntityClass, '_');
        $fieldName = substr($cleanEntityClass, 0, $lastPos);

        return $this->getAliasByActualFieldName($fieldName);
    }

    /**
     * Method will check Product attributes and see if we can get a match knowing the rules on which the
     * entity class name is generated from the attribute name.
     *
     * @param string $fieldName
     * @return string
     */
    protected function getAliasByActualFieldName($fieldName)
    {
        $productMetadata = $this->configManager->getEntityMetadata(Product::class);

        // start by lowering everything and removing underscores for easier comparisons
        $lowerProductFieldNames = [];
        foreach ($productMetadata->propertyMetadata as $property => $fieldMetadata) {
            $lowerProductFieldNames[$this->normalizeFieldName($property)] = $property;
        }

        // normalize fieldName as well
        $normalizedFieldName = $this->normalizeFieldName($fieldName);

        if (array_key_exists($normalizedFieldName, $lowerProductFieldNames)) {
            return $lowerProductFieldNames[$normalizedFieldName];
        }

        return $fieldName;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function normalizeFieldName($fieldName)
    {
        return str_replace('_', '', strtolower($fieldName));
    }

    /**
     * @param string $fieldName
     * @return array
     */
    protected function getEntityAliasAndPlural($fieldName)
    {
        $alias = 'product' . $this->normalizeFieldName($fieldName);
        return [
            $alias,
            Inflector::pluralize($alias),
        ];
    }
}
