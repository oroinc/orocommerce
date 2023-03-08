<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\TwigTemplateProperty;

/**
 * Datagrid property formatter that unwraps the plain product units and precisions data from a string and renders
 * via TWIG template.
 *
 * Expected input: %primary_unit_code%|%unit_code1%,%unit_code2%|%precision1%,%precision2%
 * Expected output: %rendered html%
 *
 * TWIG template is supplied with variable "value" containing the unit and precisions data:
 *  [
 *      [
 *          'code => 'item',
 *          'precision' => 0,
 *          'isPrimary' => false,
 *      ],
 *      // ...
 *  ]
 */
class UnitsWithPrecisionProperty extends AbstractProperty
{
    protected $excludeParams = [TwigTemplateProperty::CONTEXT_KEY, TwigTemplateProperty::TEMPLATE_KEY];

    private TwigTemplateProperty $twigTemplateProperty;

    public function __construct(TwigTemplateProperty $twigTemplateProperty)
    {
        $this->twigTemplateProperty = $twigTemplateProperty;
    }

    protected function initialize()
    {
        $this->twigTemplateProperty->init($this->params);
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return string Rendered HTML
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        $value = $record->getValue($this->getOr(self::DATA_NAME_KEY) ?: $this->get(self::NAME_KEY));

        return $this->twigTemplateProperty->render($this->unwrap((string) $value), $record);
    }

    /**
     * @param string $unitsWithPrecision
     * @return array<array{code: string, precision: int, isPriamry: bool}>
     *  [
     *      [
     *          'code => 'item',
     *          'precision' => 0,
     *          'isPrimary' => false,
     *      ],
     *      // ...
     *  ]
     */
    private function unwrap(string $unitsWithPrecision): array
    {
        $unitsWithPrecisionParts = explode('|', $unitsWithPrecision);
        if (count($unitsWithPrecisionParts) !== 3) {
            return [];
        }

        [$primaryUnit, $units, $precisions] = $unitsWithPrecisionParts;
        $units = explode(',', $units);
        $precisions = explode(',', $precisions);

        $result = [];
        foreach ($units as $index => $unitCode) {
            $isPrimary = $primaryUnit === $unitCode;
            $unitData = [
                'code' => $unitCode,
                'precision' => (int)($precisions[$index] ?? '0'),
                'isPrimary' => $isPrimary
            ];

            if ($isPrimary) {
                array_unshift($result, $unitData);
            } else {
                $result[] = $unitData;
            }
        }

        return $result;
    }
}
