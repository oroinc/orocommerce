<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitValueFormatter
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
     * @param float|integer $value
     * @param ProductUnit $unit
     * @return string
     */
    public function format($value, ProductUnit $unit)
    {
        return $this->formatCode($value, $unit->getCode());
    }

    /**
     * @param float|integer $value
     * @param ProductUnit $unit
     * @return string
     */
    public function formatShort($value, ProductUnit $unit)
    {
        return $this->formatCode($value, $unit->getCode(), true);
    }

    /**
     * @param float|integer $value
     * @param string $unitCode
     * @param boolean $isShort
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false)
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf('The parameter "value" must be a numeric, but it is of type %s.', gettype($value))
            );
        }

        $translationKey = sprintf('orob2b.product_unit.%s.value.' . ($isShort ? 'short' : 'full'), $unitCode);

        return $this->translator->transChoice($translationKey, $value, ['%count%' => $value]);
    }
}
