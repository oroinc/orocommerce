<?php

namespace Oro\Bundle\SaleBundle\Form\Extension\UnitVisibility;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductDemandOfferChoiceType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class UnitLabelQuoteProductDemandOfferChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * @var UnitValueFormatterInterface
     */
    private $unitValueFormatter;

    /**
     * @var SingleUnitModeService
     */
    private $singleUnitModeService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param UnitValueFormatterInterface $unitValueFormatter
     * @param SingleUnitModeService $singleUnitModeService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        UnitValueFormatterInterface $unitValueFormatter,
        SingleUnitModeService $singleUnitModeService,
        TranslatorInterface $translator
    ) {
        $this->unitValueFormatter = $unitValueFormatter;
        $this->singleUnitModeService = $singleUnitModeService;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return;
        }
        $resolver->setDefault('choice_label', function ($value) {
            $label = '';
            if ($value instanceof QuoteProductOffer) {
                if ($this->singleUnitModeService->isSingleUnitModeCodeVisible()
                    || !$this->singleUnitModeService->isDefaultPrimaryUnitCode($value->getProductUnitCode())
                ) {
                    $label = $this->unitValueFormatter->formatCode(
                        $value->getQuantity(),
                        $value->getProductUnitCode(),
                        true
                    );
                } else {
                    $label = $value->getQuantity();
                }
                if ($value->isAllowIncrements()) {
                    $label .= ' '.$this->translator
                            ->trans('oro.frontend.sale.quoteproductoffer.allow_increments.label');
                }
            }
            return $label;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return QuoteProductDemandOfferChoiceType::class;
    }
}
