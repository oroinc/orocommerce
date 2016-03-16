<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitLabelFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @return string
     */
    public function format($unitCode, $isShort = false, $isPlural = false)
    {
        $labelForm = ($isShort ? 'short' : 'full') . ($isPlural ? '_plural' : '');

        $translationKey = sprintf('orob2b.product_unit.%s.label.' . $labelForm, $unitCode);

        return $this->translator->trans($translationKey);
    }

    /**
     * @param array|ProductUnit[] $units
     * @param bool $isShort
     * @param bool $isPlural
     * @return array
     */
    public function formatChoices(array $units, $isShort = false, $isPlural = false)
    {
        $result = [];
        foreach ($units as $unit) {
            $result[$unit->getCode()] = $this->format($unit->getCode(), $isShort, $isPlural);
        }

        return $result;
    }
}
