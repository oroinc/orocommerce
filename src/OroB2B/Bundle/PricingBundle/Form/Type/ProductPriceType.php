<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price';

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
                    'label' => 'orob2b.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'unit',
                ProductPriceUnitSelectorType::NAME,
                [
                    'label' => 'orob2b.pricing.unit.label',
                    'empty_value' => 'orob2b.product.productunitprecision.unit_precision_required',
                    'product' => $options['product'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'label' => 'orob2b.pricing.price.label',
                    'currency_empty_value' => 'orob2b.pricing.pricelist.form.pricelist_required',
                    'full_currency_list' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'label' => 'orob2b.pricing.quantity.label',
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
