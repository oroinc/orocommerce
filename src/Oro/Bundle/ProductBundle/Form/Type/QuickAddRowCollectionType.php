<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents {@see QuickAddRowCollection} model.
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
        $builder->addModelTransformer(new ArrayToJsonTransformer());
        $builder->addModelTransformer($this->quickAddRowCollectionTransformer);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => QuickAddRowCollection::class,
                'error_bubbling' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_quick_add_row_collection';
    }
}
