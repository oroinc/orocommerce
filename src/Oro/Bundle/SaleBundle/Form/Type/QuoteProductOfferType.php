<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;

class QuoteProductOfferType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_offer';

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
                    'validation_groups' => [PriceType::OPTIONAL_VALIDATION_GROUP]
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();

        // Do not set default price for price field value if it is not set for existing offer
        // This is valid case when price can be empty on quote creation/modification step
        if ($data instanceof QuoteProductOffer) {
            $form = $event->getForm();

            $form->add(
                'price',
                PriceType::class,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'oro.sale.quoteproductoffer.price.label',
                    //Price value may be not set by user while creating quote
                    'validation_groups' => [PriceType::OPTIONAL_VALIDATION_GROUP],
                    'match_price_on_null' => false
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
                'compact_units' => false,
                'allow_prices_override' => true,
                'intention' => 'sale_quote_product_offer',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_prices_override'] = $options['allow_prices_override'];
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
