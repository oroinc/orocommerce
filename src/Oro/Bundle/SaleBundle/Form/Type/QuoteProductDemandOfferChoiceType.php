<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteProductDemandOfferChoiceType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_demand_offer_choice';

    /**
     * @var UnitValueFormatterInterface
     */
    protected $unitValueFormatter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UnitVisibilityInterface
     */
    protected $unitVisibility;

    /**
     * @param UnitValueFormatterInterface $unitValueFormatter
     * @param TranslatorInterface $translator
     * @param UnitVisibilityInterface $unitVisibility
     */
    public function __construct(
        UnitValueFormatterInterface $unitValueFormatter,
        TranslatorInterface $translator,
        UnitVisibilityInterface $unitVisibility
    ) {
        $this->unitValueFormatter = $unitValueFormatter;
        $this->translator = $translator;
        $this->unitVisibility = $unitVisibility;
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
                'choice_label' => function ($value) {
                    $label = '';
                    if ($value instanceof QuoteProductOffer) {
                        $label = $value->getQuantity();
                        if ($this->unitVisibility->isUnitCodeVisible($value->getProductUnitCode())) {
                            $label = $this->unitValueFormatter->formatCode(
                                $value->getQuantity(),
                                $value->getProductUnitCode(),
                                true
                            );
                        }
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
        return ChoiceType::class;
    }
}
