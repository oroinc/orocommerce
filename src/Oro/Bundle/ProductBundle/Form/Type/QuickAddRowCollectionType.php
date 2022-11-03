<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents {@see QuickAddRowCollection} model.
 * Inherits CollectionType to mimic the regular collection widget, but expects JSON-encoded collection on submit
 * instead.
 */
class QuickAddRowCollectionType extends AbstractType
{
    private QuickAddRowCollectionTransformer $quickAddRowCollectionTransformer;

    public function __construct(QuickAddRowCollectionTransformer $quickAddRowCollectionTransformer)
    {
        $this->quickAddRowCollectionTransformer = $quickAddRowCollectionTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        FormUtils::removeListenerByClassName(
            $builder,
            ResizeFormListener::class,
            array_keys(ResizeFormListener::getSubscribedEvents())
        );
        FormUtils::removeListenerByClassName(
            $builder,
            CollectionTypeSubscriber::class,
            array_keys(CollectionTypeSubscriber::getSubscribedEvents())
        );

        $builder->resetModelTransformers();
        $builder->resetViewTransformers();

        $builder->addModelTransformer(new ArrayToJsonTransformer());
        $builder->addModelTransformer($this->quickAddRowCollectionTransformer);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'compound' => false,
                'data_class' => QuickAddRowCollection::class,
                'error_bubbling' => false,
                'prototype_name' => '__row__',
                'entry_type' => QuickAddRowType::class,
                'handle_primary' => false,
                'row_count_add' => 5,
                'row_count_initial' => 8,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_quick_add_row_collection';
    }
}
