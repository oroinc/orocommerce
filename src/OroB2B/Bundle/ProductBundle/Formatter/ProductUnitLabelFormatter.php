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
     * @return string
     */
    public function format($unitCode, $isShort = false)
    {
        $translationKey = sprintf('orob2b.product_unit.%s.label.' . ($isShort ? 'short' : 'full'), $unitCode);

        return $this->translator->trans($translationKey);
    }

    /**
     * @param array|ProductUnit[] $units
     * @param bool $isShort
     * @return array
     */
    public function formatChoices(array $units, $isShort = false)
    {
        $result = [];
        foreach ($units as $unit) {
            $result[$unit->getCode()] = $this->format($unit->getCode(), $isShort);
        }

        return $result;
    }
}
