<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Product prices form type
 * Used to group other related types for Product Price item
 */
class ProductPriceType extends AbstractType
{
    const NAME = 'oro_pricing_product_price';

    /** @var string */
    protected $dataClass;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'priceList',
                PriceListSelectType::class,
                [
                    'label' => 'oro.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'unit',
                ProductPriceUnitSelectorType::class,
                [
                    'label' => 'oro.pricing.unit.label',
                    'placeholder' => 'oro.product.productunitprecision.unit_precision_required',
                    'product' => $options['product'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'label' => 'oro.pricing.quantity.label'
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);

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

    public function onPreSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        $form = $event->getForm();
        $productPrice = $form->getData();

        if (!$productPrice instanceof ProductPrice) {
            return;
        }

        $priceForm = $form->get('price');
        if ($submittedData['quantity'] != $form->get('quantity')->getViewData()
            || $submittedData['unit'] != $productPrice->getProductUnitCode()
            || $submittedData['price']['value'] != $priceForm->get('value')->getViewData()
            || $submittedData['price']['currency'] != $priceForm->get('currency')->getViewData()
        ) {
            $productPrice->setPriceRule(null);
        }
    }

    /**
     * Adds Price lists currencies to select even if there are no such system currencies
     * Fetches full currency list only for add new collection item form type template
     */
    public function onPreSetData(FormEvent $event)
    {
        $productPrice = $event->getData();
        $form = $event->getForm();
        $isFullCurrencyList = true;
        $currencies = null;

        if ($productPrice instanceof ProductPrice && $productPrice->getPriceList()) {
            $currencies = $productPrice->getPriceList()->getCurrencies();
            $isFullCurrencyList = false;
        }

        $form->add(
            'price',
            PriceType::class,
            [
                'label' => 'oro.pricing.price.label',
                'currency_empty_value' => 'oro.pricing.pricelist.form.pricelist_required',
                'currencies_list' => $currencies,
                'full_currency_list' => $isFullCurrencyList
            ]
        );
    }

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
