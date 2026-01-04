<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Product shipping options collection type.
 */
class ProductShippingOptionsCollectionType extends AbstractType
{
    public const NAME = 'oro_shipping_product_shipping_options_collection';

    /** @var string */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ProductShippingOptionsType::class,
                'show_form_when_empty' => false,
                'entry_options' => [
                    'data_class' => $this->dataClass
                ],
                'check_field_name' => null,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
