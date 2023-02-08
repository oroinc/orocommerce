<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The factory to create Multi Shipping method.
 */
class MultiShippingMethodFromChannelFactory implements IntegrationShippingMethodFactoryInterface
{
    private IntegrationIdentifierGeneratorInterface $identifierGenerator;
    private TranslatorInterface $translator;
    private RoundingServiceInterface $roundingService;
    private MultiShippingCostProvider $shippingCostProvider;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        TranslatorInterface $translator,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->translator = $translator;
        $this->roundingService = $roundingService;
        $this->shippingCostProvider = $shippingCostProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel): ShippingMethodInterface
    {
        return new MultiShippingMethod(
            $this->identifierGenerator->generateIdentifier($channel),
            $this->translator->trans('oro.shipping.multi_shipping_method.label'),
            'bundles/oroshipping/img/multi-shipping-logo.png',
            $channel->isEnabled(),
            $this->roundingService,
            $this->shippingCostProvider
        );
    }
}
