<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;

class QuoteProductOfferType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_offer';

    /**
     * @var QuoteProductOfferFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param QuoteProductOfferFormatter $formatter
     */
    public function __construct(QuoteProductOfferFormatter $formatter)
    {
        $this->formatter = $formatter;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'price',
                PriceType::NAME,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'oro.sale.quoteproductoffer.price.label',
                ]
            )
            ->add(
                'priceType',
                'hidden',
                [
                    // TODO: enable once fully supported on the quote views and in orders
                    'data' => QuoteProductOffer::PRICE_TYPE_UNIT,
                ]
            )
            ->add(
                'allowIncrements',
                'checkbox',
                [
                    'required' => false,
                    'label' => 'oro.sale.quoteproductoffer.allow_increments.label',
                    'attr' => [
                        'default' => true,
                    ],
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'oro.product.productunit.entity_label',
                    'required' => true,
                    'compact' => $options['compact_units'],
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.sale.quoteproductoffer.quantity.label',
                    'default_data' => 1,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'compact_units' => false,
                'intention' => 'sale_quote_product_offer',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
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
}
