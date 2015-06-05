<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
                ['label' => 'orob2b.pricing.pricelist.entity_label', 'create_enabled' => false, 'required' => true]
            )
            ->add(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.pricing.unit.label',
                    'empty_value' => 'orob2b.product.productunit.form.choose',
                ]
            )
            ->add('price', PriceType::NAME, ['label' => 'orob2b.pricing.price.label', 'full_currency_list' => true])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $precision = null;

            if ($data && $data->getProduct()) {
                /** @var Product $product */
                $product = $data->getProduct();
                $productUnitPrecisions = $product->getUnitPrecisions();


                foreach ($productUnitPrecisions as $productUnitPrecision) {
                    if ($productUnitPrecision->getUnit() == $data->getUnit()) {
                        $precision = $productUnitPrecision->getPrecision();
                    }
                }
            }

            $form->add('quantity', 'number', [
                'label' => 'orob2b.pricing.quantity.label',
                'precision' => $precision
            ]);
        });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
