<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductAttributePriceType extends AbstractType implements DataMapperInterface
{
    const NAME = 'oro_pricing_product_attribute_price';
    const PRICE = 'price';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PRICE, 'number', [
            'scale' => Price::MAX_VALUE_PRECISION
        ])
            ->setDataMapper($this);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => PriceAttributeProductPrice::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $priceForm */
        $priceForm = $forms[self::PRICE];
        /** @var Price $price */
        $price = $data ? $data->getPrice() : null;
        $priceForm->setData($price ? $price->getValue() : null);
    }

    /**
     * {@inheritdoc}
     * @param PriceAttributeProductPrice $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $priceForm */
        $priceForm = $forms[self::PRICE];
        $price = Price::create($priceForm->getData(), $data->getPrice()->getCurrency());
        $data->setPrice($price);
    }
}
