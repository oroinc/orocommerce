<?php

namespace Oro\Bundle\FixedProductShippingBundle\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethod;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;

/**
 * Factory that creates shipping method from the channel.
 */
class FixedProductMethodFromChannelFactory implements IntegrationShippingMethodFactoryInterface
{
    protected IntegrationIdentifierGeneratorInterface $identifierGenerator;
    protected LocalizationHelper $localizationHelper;
    protected IntegrationIconProviderInterface $integrationIconProvider;
    protected RoundingServiceInterface $roundingService;
    protected ShippingCostProvider $shippingCostProvider;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        IntegrationIconProviderInterface $integrationIconProvider,
        RoundingServiceInterface $roundingService,
        ShippingCostProvider $shippingCostProvider
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->integrationIconProvider = $integrationIconProvider;
        $this->roundingService = $roundingService;
        $this->shippingCostProvider = $shippingCostProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Channel $channel): FixedProductMethod
    {
        $id = $this->identifierGenerator->generateIdentifier($channel);
        $label = $this->getChannelLabel($channel);
        $icon = $this->getIcon($channel);

        return new FixedProductMethod(
            $id,
            $label,
            $icon,
            $channel->isEnabled(),
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    protected function getChannelLabel(Channel $channel): string
    {
        /** @var FixedProductSettings $transport */
        $transport = $channel->getTransport();
        return (string) $this->localizationHelper->getLocalizedValue($transport->getLabels());
    }

    protected function getIcon(Channel $channel): string
    {
        return $this->integrationIconProvider->getIcon($channel);
    }
}
