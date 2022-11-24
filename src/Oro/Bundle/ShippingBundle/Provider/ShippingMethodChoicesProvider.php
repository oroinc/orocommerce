<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides choices for form types to select shipping methods.
 */
class ShippingMethodChoicesProvider
{
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private TranslatorInterface $translator;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        TranslatorInterface $translator
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->translator = $translator;
    }

    public function getMethods(bool $translate = false): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if (!$shippingMethod->isEnabled()) {
                continue;
            }

            $label = $shippingMethod->getLabel();
            if ($translate) {
                $label = $this->translator->trans($label);
            }

            $result[$label] = $shippingMethod->getIdentifier();
        }

        return $result;
    }
}
