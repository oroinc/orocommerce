<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides an array of the shipping methods.
 */
class BasicShippingMethodChoicesProvider implements ShippingMethodChoicesProviderInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        TranslatorInterface $translator
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods($translate = false)
    {
        return array_reduce(
            $this->shippingMethodProvider->getShippingMethods(),
            function (array $result, ShippingMethodInterface $method) use ($translate) {
                $label = $method->getLabel();
                if ($translate) {
                    $label = $this->translator->trans($label);
                }
                //cannot guarantee uniqueness of shipping name
                //need to be sure that we wouldn't override exists one
                if (array_key_exists($label, $result)) {
                    $label .= $this->getShippingMethodIdLabel($method);
                }

                $result[$label] = $method->getIdentifier();

                return $result;
            },
            []
        );
    }

    private function getShippingMethodIdLabel(ShippingMethodInterface $shippingMethod): string
    {
        //extract entity identifier flat_rate_4 => 4
        $id = substr($shippingMethod->getIdentifier(), strrpos($shippingMethod->getIdentifier(), '_') + 1);
        return " ($id)";
    }
}
