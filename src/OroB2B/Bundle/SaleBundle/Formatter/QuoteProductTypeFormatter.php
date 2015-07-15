<?php

namespace OroB2B\Bundle\SaleBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

class QuoteProductTypeFormatter
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
     * @param string $type
     * @return string
     */
    public function formatTypeLabel($type)
    {
        $translationKey = sprintf('orob2b.sale.quoteproduct.type.%s', $type);

        return $this->translator->trans($translationKey);
    }

    /**
     * @param array $types
     * @return array
     */
    public function formatTypeLabels(array $types)
    {
        $res = [];

        foreach ($types as $key => $value) {
            $res[$key] = $this->formatTypeLabel($value);
        }

        return $res;
    }
}
