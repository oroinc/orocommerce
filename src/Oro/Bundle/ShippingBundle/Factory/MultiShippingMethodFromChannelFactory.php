<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Factory that creates shipping method from the channel.
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

    public function create(Channel $channel): MultiShippingMethod
    {
        $id = $this->identifierGenerator->generateIdentifier($channel);
        $label = $this->getChannelLabel();
        $icon = $this->getIcon();

        return new MultiShippingMethod(
            $id,
            $label,
            $icon,
            $channel->isEnabled(),
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    protected function getChannelLabel(): string
    {
        return $this->translator->trans('oro.multi_shipping_method.label');
    }

    protected function getIcon(): string
    {
        return 'bundles/oroshipping/img/multi-shipping-logo.png';
    }
}
