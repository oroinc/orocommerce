<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price';

    /** @var  string */
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
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.pricing.unit.label',
                    'empty_value' => 'orob2b.product.productunit.form.choose',
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'label' => 'orob2b.pricing.price.label',
                    'full_currency_list' => true,
                ]
            );

        // make value not empty
        $builder->get('price')
            ->remove('value')
            ->add(
                'value',
                'number',
                [
                    'required' => true,
                    'constraints' => [new NotBlank(), new Range(['min' => 0]), new Decimal()]
                ]
            );

        $this->addListeners($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addListeners(FormBuilderInterface $builder)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var ProductPrice $data */
                $data = $event->getData();
                $form = $event->getForm();
                $precision = null;

                if ($data && $data->getProduct()) {
                    /** @var Product $product */
                    $product = $data->getProduct();
                    $precision = $this->getPrecision($product, $data->getUnit()->getCode());
                }

                $this->addQuantity($form, $precision);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $precision = null;

                if ($data && array_key_exists('unit', $data) && $form->getData()) {
                    $unitCode = $data['unit'];

                    /** @var Product $product */
                    $product = $form->getData()->getProduct();
                    $precision = $this->getPrecision($product, $unitCode);
                }

                $this->addQuantity($form, $precision, true);
            }
        );

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
     * @param Product $product
     * @param string $unitCode
     * @return int|null
     */
    protected function getPrecision(Product $product, $unitCode)
    {
        $precision = null;
        $productUnitPrecisions = $product->getUnitPrecisions();
        foreach ($productUnitPrecisions as $productUnitPrecision) {
            if ($productUnitPrecision->getUnit() && $productUnitPrecision->getUnit()->getCode() === $unitCode) {
                $precision = $productUnitPrecision->getPrecision();
            }
        }

        return $precision;
    }

    /**
     * @param FormInterface $form
     * @param mixed $precision
     * @param bool $force
     */
    protected function addQuantity(FormInterface $form, $precision, $force = false)
    {
        if ($force && $form->has('quantity')) {
            $form->remove('quantity');
        }

        $form
            ->add(
                'quantity',
                'number',
                [
                    'label' => 'orob2b.pricing.quantity.label',
                    'precision' => $precision,
                    'constraints' => [new NotBlank(), new Range(['min' => 0]), new Decimal()],
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass
        ]);
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
        return self::NAME;
    }
}
