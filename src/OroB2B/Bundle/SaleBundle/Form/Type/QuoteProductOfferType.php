<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;

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
            ->add('quantity', 'integer', [
                'required' => true,
                'label' => 'orob2b.sale.quoteproductoffer.quantity.label'
            ])
            ->add('price', PriceType::NAME, [
                'currency_empty_value' => null,
                'error_bubbling' => false,
                'required' => true,
                'label' => 'orob2b.sale.quoteproductoffer.price.label'
            ])
            ->add('priceType', 'hidden', [
                'data' => QuoteProductOffer::PRICE_TYPE_UNIT,
                // TODO: enable once fully supported on the quote views and in orders
                //'label' => 'orob2b.sale.quoteproductoffer.price_type.label',
                //'choices' => $this->formatter->formatPriceTypeLabels(QuoteProductOffer::getPriceTypes()),
                //'required' => true,
                //'expanded' => true,
            ])
            ->add('allowIncrements', 'checkbox', [
                'required' => false,
                'label' => 'orob2b.sale.quoteproductoffer.allow_increments.label',
                'attr' => [
                    'default' => true,
                ],
            ])
            ->add('productUnit', ProductUnitRemovedSelectionType::NAME, [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'sale_quote_product_offer',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
//        // Set priceType to PRICE_TYPE_UNIT by default
//        $priceType = $event->getForm()->get('priceType');
//        if (null === $priceType->getData()) {
//            $priceType->setData(QuoteProductOffer::PRICE_TYPE_UNIT);
//        }

        // Set quantity to 1 by default
        $quantity = $event->getForm()->get('quantity');
        if (null === $quantity->getData()) {
            $quantity->setData(1);
        }
    }
}
