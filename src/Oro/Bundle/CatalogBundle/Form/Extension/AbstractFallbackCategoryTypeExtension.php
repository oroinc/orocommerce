<?php

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The main purpose of this extension is optimize processing of fallback properties
 */
abstract class AbstractFallbackCategoryTypeExtension extends AbstractTypeExtension
{
    /**
     * @return array|null
     */
    abstract public function getFallbackProperties();

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CategoryType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $category = $builder->getData();
        $fallbackProperties = $this->getFallbackProperties();
        if ($category instanceof Category && $fallbackProperties !== null) {
            foreach ($fallbackProperties as $fallbackProperty) {
                $this->processFallbackProperty($category, $fallbackProperty);
            }
        }
    }

    /**
     * Use fallback object in case of the value of property equals to null.
     * (For specific properties that marked as property that have fallback value.)
     *
     * @param Category $category
     * @param string   $fallbackProperty
     */
    private function processFallbackProperty(Category $category, $fallbackProperty)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if ($propertyAccessor->getValue($category, $fallbackProperty) !== null) {
            return;
        }

        $entityFallback = new EntityFieldFallbackValue();
        // set default fallback (systemConfig or parentCategory)
        if ($category->getParentCategory()) {
            $entityFallback->setFallback(ParentCategoryFallbackProvider::FALLBACK_ID);
        } else {
            $entityFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
        }
        $propertyAccessor->setValue($category, $fallbackProperty, $entityFallback);
    }
}
