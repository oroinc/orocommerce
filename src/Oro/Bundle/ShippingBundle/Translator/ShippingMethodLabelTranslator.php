<?php

namespace Oro\Bundle\ShippingBundle\Translator;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingMethodLabelTranslator
{
    /** @var ShippingMethodLabelFormatter */
    private $formatter;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ShippingMethodLabelFormatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     *
     * @return string
     */
    public function getShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
    {
        $label = $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName);

        return $this->translator->trans($label);
    }
}
