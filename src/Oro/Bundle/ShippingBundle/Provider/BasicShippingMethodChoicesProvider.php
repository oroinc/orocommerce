<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
                $result[$label] = $method->getIdentifier();

                return $result;
            },
            []
        );
    }
}
