<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

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
}
