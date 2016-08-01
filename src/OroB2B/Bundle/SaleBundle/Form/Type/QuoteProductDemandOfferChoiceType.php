<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductDemandOfferChoiceType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_demand_offer_choice';

    /**
     * @var ProductUnitValueFormatter
     */
    protected $unitValueFormatter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ProductUnitValueFormatter $unitValueFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(ProductUnitValueFormatter $unitValueFormatter, TranslatorInterface $translator)
    {
        $this->unitValueFormatter = $unitValueFormatter;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'expanded' => true,
                'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'multiple' => false,
                'choices_as_values' => true,
                'choice_label' => function ($value) {
                    $label = '';
                    if ($value instanceof QuoteProductOffer) {
                        $label = $this->unitValueFormatter->formatCode(
                            $value->getQuantity(),
                            $value->getProductUnitCode(),
                            true
                        );
                        if ($value->isAllowIncrements()) {
                            $label .=  ' ' . $this->translator
                                ->trans('orob2b.frontend.sale.quoteproductoffer.allow_increments.label');
                        }
                    }
                    return $label;
                }
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
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
