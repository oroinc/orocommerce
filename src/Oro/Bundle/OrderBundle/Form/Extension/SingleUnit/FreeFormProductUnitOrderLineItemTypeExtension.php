<?php

namespace Oro\Bundle\OrderBundle\Form\Extension\SingleUnit;

use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FreeFormProductUnitOrderLineItemTypeExtension extends AbstractTypeExtension
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component_options'] = array_merge($view->vars['page_component_options'], [
            'freeFormUnits' => $this->getFreeFormUnits(),
        ]);
    }

    /**
     * @return array
     */
    private function getFreeFormUnits()
    {
        $defaultUnit = $this->singleUnitModeService->getConfigDefaultUnit();
        return $this->unitLabelFormatter->formatChoices([$defaultUnit]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::class;
    }
}
