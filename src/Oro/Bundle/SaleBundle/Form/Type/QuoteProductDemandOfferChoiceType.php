<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductDemandOfferChoiceType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_demand_offer_choice';

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
                                ->trans('oro.frontend.sale.quoteproductoffer.allow_increments.label');
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
