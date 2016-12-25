<?php

namespace Oro\Bundle\SaleBundle\Form\Extension\SingleUnit;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FreeFormProductUnitQuoteProductTypeExtension extends AbstractTypeExtension
{
    /**
     * @var UnitLabelFormatterInterface
     */
    private $unitLabelFormatter;

    /**
     * @var SingleUnitModeService
     */
    private $singleUnitModeService;

    /**
     * @param UnitLabelFormatterInterface $unitLabelFormatter
     * @param SingleUnitModeService $singleUnitModeService
     */
    public function __construct(
        UnitLabelFormatterInterface $unitLabelFormatter,
        SingleUnitModeService $singleUnitModeService
    ) {
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $compactUnits = array_key_exists('compact_units', $options) ? $options['compact_units'] : false;
        $view->vars['componentOptions'] = array_merge($view->vars['componentOptions'], [
            'allUnits' => $this->getFreeFormUnits($compactUnits),
        ]);
    }

    /**
     * @param bool $compactUnits
     * @return array
     */
    private function getFreeFormUnits($compactUnits)
    {
        $defaultUnit = $this->singleUnitModeService->getConfigDefaultUnit();
        return $this->unitLabelFormatter->formatChoices([$defaultUnit], $compactUnits);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return QuoteProductType::class;
    }
}
