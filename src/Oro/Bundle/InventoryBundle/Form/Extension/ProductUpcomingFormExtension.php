<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds upcoming fields to product form.
 */
class ProductUpcomingFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
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
        // set default fallback setting
        $product = $event->getData();
        $accessor = PropertyAccess::createPropertyAccessor();

        if (!$accessor->getValue($product, UpcomingProductProvider::IS_UPCOMING)) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $accessor->setValue($product, UpcomingProductProvider::IS_UPCOMING, $entityFallback);
        }
    }

    public function onPostSubmit(FormEvent $event)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var Product $product */
        $product = $event->getData();
        /** @var EntityFieldFallbackValue|null $entityFallback */
        $entityFallback = $accessor->getValue($product, UpcomingProductProvider::IS_UPCOMING);

        if (!$entityFallback || $entityFallback->getFallback() || !$entityFallback->getOwnValue()) {
            $accessor->setValue($product, UpcomingProductProvider::AVAILABILITY_DATE, null);
        }
    }
}
