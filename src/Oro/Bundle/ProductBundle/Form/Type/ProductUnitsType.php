<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting product units.
 *
 * This form type provides a choice field populated with all available product units,
 * allowing users to select from the configured units of measure in the system.
 */
class ProductUnitsType extends AbstractType
{
    public const NAME = 'oro_product_units';

    /**
     * @var  ProductUnitsProvider
     */
    protected $productUnitsProvider;

    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->productUnitsProvider->getAvailableProductUnits(),
        ));
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
