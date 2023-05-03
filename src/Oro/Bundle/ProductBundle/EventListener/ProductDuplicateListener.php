<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Clone extend attributes and link them with duplicate product
 */
class ProductDuplicateListener
{
    private ConfigManager $configManager;
    private ConfigProvider $attributeProvider;
    private DoctrineHelper $doctrineHelper;
    private PropertyAccessor $propertyAccessor;

    public function __construct(
        ConfigManager $configManager,
        ConfigProvider $attributeProvider,
        DoctrineHelper $doctrineHelper,
        PropertyAccessor $propertyAccessor
    ) {
        $this->configManager = $configManager;
        $this->attributeProvider = $attributeProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function onDuplicateAfter(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();
        $em = $this->doctrineHelper->getEntityManager($product::class);

        /** @var Config $attribute */
        foreach ($this->getExtendAttributes() as $attribute) {
            $fieldName = $this->getFieldName($attribute);

            if (!$fieldName) {
                continue;
            }

            $value = $this->propertyAccessor->getValue($sourceProduct, $fieldName);
            $newValue = $this->cloneValue($em, $value);

            if ($newValue) {
                $this->propertyAccessor->setValue($product, $fieldName, $newValue);
            }
        }

        $em->flush($product);
    }

    private function getExtendAttributes(): array
    {
        return $this->configManager->getProvider('extend')->filter(function (Config $config) {
            return $config->is('is_extend')
                && $config->is('target_entity', LocalizedFallbackValue::class)
                && $config->is('owner', 'Custom');
        }, Product::class);
    }

    private function getFieldName(Config $attribute): string
    {
        $attributeName = $attribute->getId()->getFieldName();
        $attributeConfig = $this->attributeProvider->getConfig(Product::class, $attributeName);
        return $attributeConfig->get('field_name') ?: $attributeName;
    }

    private function cloneValue(EntityManagerInterface $em, mixed $value): ?object
    {
        if (\is_iterable($value)) {
            $newCollection = new ArrayCollection();

            foreach ($value as $field) {
                $newField = clone $field;
                $em->persist($newField);
                $newCollection->add($newField);
            }

            return $newCollection;
        }

        if (is_object($value)) {
            $newValue = clone $value;
            $em->persist($newValue);

            return $newValue;
        }

        return null;
    }
}
