<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
                'error_bubbling' => false,
                'required' => true,
                'label' => 'orob2b.sale.quoteproductoffer.price.label'
            ])
            ->add('priceType', 'choice', [
                'label' => 'orob2b.sale.quoteproductoffer.price_type.label',
                'choices' => $this->formatter->formatPriceTypeLabels(QuoteProductOffer::getPriceTypes()),
                'required' => true,
            ])
            ->add('allowIncrements', 'checkbox', [
                'required' => false,
                'label' => 'orob2b.sale.quoteproductoffer.allow_increments.label'
            ])
            ->add('productUnit', ProductUnitRemovedSelectionType::NAME, [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
            ]);
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
}
