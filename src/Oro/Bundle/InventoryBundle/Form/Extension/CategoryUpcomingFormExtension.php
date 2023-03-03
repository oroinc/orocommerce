<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds upcoming fields to category form.
 */
class CategoryUpcomingFormExtension extends AbstractTypeExtension
{
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
        $builder
            ->add(
                UpcomingProductProvider::IS_UPCOMING,
                EntityFieldFallbackValueType::class,
                [
                    'value_options' => [
                        'choices' => [
                            'oro.inventory.is_upcoming.choice.false' => 0,
                            'oro.inventory.is_upcoming.choice.true' => 1,
                        ]
                    ],
                ]
            )
            ->add(UpcomingProductProvider::AVAILABILITY_DATE, OroDateTimeType::class, [
                'required' => false,
                'years' => [
                    date_create('-10 year')->format('Y'),
                    date_create('+30 year')->format('Y')
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPreSetData(FormEvent $event)
    {
        /** @var Category $category */
        $category = $event->getData();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if (!$propertyAccessor->getValue($category, UpcomingProductProvider::IS_UPCOMING)) {
            $entityFallback = new EntityFieldFallbackValue();
            if ($category->getParentCategory()) {
                $entityFallback->setFallback(ParentCategoryFallbackProvider::FALLBACK_ID);
            }
            $propertyAccessor->setValue($category, UpcomingProductProvider::IS_UPCOMING, $entityFallback);
        }
    }

    public function onPostSubmit(FormEvent $event)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var Category $category */
        $category = $event->getData();
        /** @var EntityFieldFallbackValue|null $entityFallback */
        $entityFallback = $accessor->getValue($category, UpcomingProductProvider::IS_UPCOMING);

        if (!$entityFallback || $entityFallback->getFallback() || !$entityFallback->getOwnValue()) {
            $accessor->setValue($category, UpcomingProductProvider::AVAILABILITY_DATE, null);
        }
    }
}
