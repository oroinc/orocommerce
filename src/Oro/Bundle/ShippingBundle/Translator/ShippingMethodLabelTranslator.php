<?php

namespace Oro\Bundle\ShippingBundle\Translator;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The shipping method label translator.
 */
class ShippingMethodLabelTranslator
{
    private ShippingMethodLabelFormatter $formatter;
    private TranslatorInterface $translator;

    public function __construct(
        ShippingMethodLabelFormatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    public function getShippingMethodWithTypeLabel(?string $shippingMethodName, ?string $shippingTypeName): string
    {
        return $this->translator->trans(
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }
}
