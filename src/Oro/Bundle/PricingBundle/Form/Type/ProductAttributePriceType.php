<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds price value field and handles mapping it correct
 */
class ProductAttributePriceType extends AbstractType implements DataMapperInterface
{
    public const NAME = 'oro_pricing_product_attribute_price';
    public const PRICE = 'price';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PRICE, NumberType::class, [
            'scale' => Price::MAX_VALUE_SCALE
        ])
            ->setDataMapper($this);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => PriceAttributeProductPrice::class
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
    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $priceForm */
        $priceForm = $forms[self::PRICE];
        /** @var Price $price */
        $price = $data ? $data->getPrice() : null;
        $priceForm->setData($price ? $price->getValue() : null);
    }

    /**
     * @param PriceAttributeProductPrice $data
     */
    #[\Override]
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $priceForm */
        $priceForm = $forms[self::PRICE];
        $price = Price::create($priceForm->getData(), $data->getPrice()->getCurrency());
        $data->setPrice($price);
    }
}
