<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;


class ProductEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var AttributeConfigHelper */
    private $attributeConfigHelper;

    /** @var string */
    private $extendedProductPrefix;

    public function __construct(AttributeConfigHelper $attributeConfigHelper)
    {
        $this->attributeConfigHelper = $attributeConfigHelper;
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
        $fieldName = $this->getFieldNameFromEntityClass($entityClass);
        if ($this->attributeConfigHelper->isFieldAttribute(Product::class, $fieldName)) {
            list($alias, $plural) = $this->getEntityAliasAndPlural($fieldName);

            return new EntityAlias($alias, $plural);
        }

        return null;
    }

    /**
     * Ex: Extend\Entity\EV_Product_New_Attribute_8fde6396
     *
     * @param $entityClass
     * @return string
     */
    private function getFieldNameFromEntityClass($entityClass)
    {
        // starting with a FQN like Extend\Entity\EV_Product_New_Attribute_8fde6396

        // we get a clean entity class name like
        // ex: New_Attribute_8fde6396
        $cleanEntityClass = str_replace($this->extendedProductPrefix, '', $entityClass);

        // and if we remove the hash from the class name we get the field name
        // ex: new_attribute
        $lastPos = strrpos($cleanEntityClass, '_');

        return strtolower(substr($cleanEntityClass, 0, $lastPos));
    }

    /**
     * @param $fieldName
     * @return array
     */
    private function getEntityAliasAndPlural($fieldName)
    {
        return [
            strtolower($fieldName),
            strtolower(Inflector::pluralize($fieldName))
        ];
    }
}
