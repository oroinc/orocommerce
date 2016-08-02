<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class PriceListProductPriceType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_product_price';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ProductPrice $data */
        $data = $builder->getData();
        $isExisting = $data && $data->getId();

        $additionalCurrencies = [];
        if ($data->getPriceList()) {
            $additionalCurrencies = $data->getPriceList()->getCurrencies();
        }

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.product.label',
                    'create_enabled' => false,
                    'disabled' => $isExisting,
                    'error_bubbling' => true,
                ]
            )
            ->add(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.unit.label',
                    'placeholder' => 'orob2b.product.form.product_required',
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.quantity.label',
                    'product_holder' => $data,
                    'product_unit_field' => 'unit'
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'required' => true,
                    'compact' => true,
                    'label' => 'orob2b.pricing.productprice.price.label',
                    'additional_currencies' => $additionalCurrencies,
                    'currency_empty_value' => false,
                    'by_reference' => false,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if ($data instanceof ProductPrice && $data->getId()) {
            $event->getForm()
                ->remove('unit')
                ->add(
                    'unit',
                    ProductUnitSelectionType::NAME,
                    [
                        'required' => true,
                        'label' => 'orob2b.pricing.productprice.unit.label',
                        'placeholder' => false
                    ]
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
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
     * @param string $productClass
     * @return PriceListProductPriceType
     */
    public function setDataClass($productClass)
    {
        $this->dataClass = $productClass;

        return $this;
    }
}
