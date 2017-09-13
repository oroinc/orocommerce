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
        $fieldName = $this->getFieldNameFromEntityClass($entityClass);
        if ($this->attributeConfigHelper->isFieldAttribute(Product::class, $fieldName)) {
            list($alias, $plural) = $this->getEntityAliasAndPlural($fieldName);

            return new EntityAlias($alias, $plural);
        }

        return null;
    }

    /**
     * @param $entityClass ex:Extend\Entity\EV_Product_New_Attribute_8fde6396
     * @return string
     */
    private function getFieldNameFromEntityClass($entityClass)
    {
        // starting with a FQN like Extend\Entity\EV_Product_New_Attribute_8fde6396 we get a clean entity class name
        // ex: New_Attribute_8fde6396
        $cleanEntityClass = str_replace($this->extendedProductPrefix, '', $entityClass);

        // and if we remove the hash from the class name we get the field name
        // ex: New_Attribute
        $lastPos = strrpos($cleanEntityClass, '_');
        $fieldName = substr($cleanEntityClass, 0, $lastPos);

        return $this->getActualFieldName($fieldName);
    }

    /**
     * Method will check Product attributes and see if we can get a match knowing the rules on which the
     * entity class name is generated from the attribute name.
     *
     * @param $fieldName
     * @return string
     */
    private function getActualFieldName($fieldName)
    {
        $productMetadata = $this->configManager->getEntityMetadata(Product::class);

        // start by lowering everything to have a case standard
        $lowerProductFieldNames = [];
        foreach ($productMetadata->propertyMetadata as $property => $fieldMetadata) {
            $lowerProductFieldNames[strtolower($property)] = $property;
        }

        // then check if one matches
        foreach ($this->getFieldPossibleNames($fieldName) as $possibleName) {
            if (array_key_exists($possibleName, $lowerProductFieldNames)) {
                return $lowerProductFieldNames[$possibleName];
            }
        }

        return $fieldName;
    }

    /**
     * @param $fieldName
     * @return array
     */
    private function getFieldPossibleNames($fieldName)
    {
        $possibleFieldNames = [];

        // case 1. actual field name is simple, for example "attribute"
        // generated class name: Extend\Entity\EV_Product_Attribute_E7f71b95
        $possibleFieldNames[$fieldName] = ($fieldName = strtolower($fieldName));

        // case 2. actual field contains underscores, for example "my_test_attribute"
        // generated class name: Extend\Entity\EV_Product_My__Test__Attribute_B9ddb488
        $possibleFieldNames[$fieldName] = ($fieldName = str_replace('__', '_', $fieldName));

        // case 3. actual field name is camelCase, for example "testAttribute"
        // generated class name: Extend\Entity\EV_Product_Test_Attribute_D913a397
        $possibleFieldNames[$fieldName] = ($fieldName = str_replace('_', '', $fieldName));

        return $possibleFieldNames;
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
