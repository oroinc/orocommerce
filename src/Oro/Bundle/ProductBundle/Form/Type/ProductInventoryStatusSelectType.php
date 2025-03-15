<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Enum Choice Type for product inventory status.
 *
 * available choices selecting from `oro_enum_option` table by `enum_code`
 */
class ProductInventoryStatusSelectType extends AbstractType
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new ReversedTransformer(new EntitiesToIdsTransformer($this->doctrine, EnumOption::class))
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'enum_code' => 'prod_inventory_status',
                'multiple' => true
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EnumChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_product_inventory_status_select';
    }
}
