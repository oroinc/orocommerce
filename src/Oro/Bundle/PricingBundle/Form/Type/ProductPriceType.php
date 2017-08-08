<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceType extends AbstractType
{
    const NAME = 'oro_pricing_product_price';

    /** @var string */
    protected $dataClass;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'label' => 'oro.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'unit',
                ProductPriceUnitSelectorType::NAME,
                [
                    'label' => 'oro.pricing.unit.label',
                    'empty_value' => 'oro.product.productunitprecision.unit_precision_required',
                    'product' => $options['product'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'label' => 'oro.pricing.price.label',
                    'currency_empty_value' => 'oro.pricing.pricelist.form.pricelist_required',
                    'full_currency_list' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'label' => 'oro.pricing.quantity.label',
                    'product' => $options['product'],
                    'product_unit_field' => 'unit',
                ]
            );

        // make value not empty
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var ProductPrice $price */
                $price = $event->getData();
                if ($price) {
                    $price->updatePrice();
                }
            }
        );
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        $productPrice = $event->getForm()->getData();
        if (!$productPrice instanceof ProductPrice) {
            return;
        }
        $oldPrice = $productPrice->getPrice();
        if ($submittedData['quantity'] != $productPrice->getQuantity()
            || $submittedData['unit'] != $productPrice->getUnit()->getCode()
            || $submittedData['price']['value'] != $oldPrice->getValue()
            || $submittedData['price']['currency'] != $oldPrice->getCurrency()
        ) {
            $productPrice->setPriceRule(null);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'product' => null,
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
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
}
