<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;

class ProductUpcomingFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                ProductUpcomingProvider::IS_UPCOMING,
                EntityFieldFallbackValueType::class,
                [
                    'value_options' => [
                        'choices' => [
                            'oro.inventory.is_upcoming.choice.false' => 0,
                            'oro.inventory.is_upcoming.choice.true' => 1,
                        ],
                        // TODO: Remove 'choices_as_values' option in scope of BAP-15236
                        'choices_as_values' => true,
                    ],
                ]
            )
            ->add(ProductUpcomingProvider::AVAILABILITY_DATE, OroDateTimeType::class, [
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        // set default fallback setting
        $product = $event->getData();
        $accessor = PropertyAccess::createPropertyAccessor();

        if (!$accessor->getValue($product, ProductUpcomingProvider::IS_UPCOMING)) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $accessor->setValue($product, ProductUpcomingProvider::IS_UPCOMING, $entityFallback);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var Product $product */
        $product = $event->getData();
        /** @var EntityFieldFallbackValue|null $entityFallback */
        $entityFallback = $accessor->getValue($product, ProductUpcomingProvider::IS_UPCOMING);

        if (!$entityFallback || $entityFallback->getFallback() || !$entityFallback->getOwnValue()) {
            $accessor->setValue($product, ProductUpcomingProvider::AVAILABILITY_DATE, null);
        }
    }
}
