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
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * The factory to create Fixed Product shipping method.
 */
class FixedProductMethodFromChannelFactory implements IntegrationShippingMethodFactoryInterface
{
    private IntegrationIdentifierGeneratorInterface $identifierGenerator;
    private LocalizationHelper $localizationHelper;
    private IntegrationIconProviderInterface $integrationIconProvider;
    private RoundingServiceInterface $roundingService;
    private ShippingCostProvider $shippingCostProvider;

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
     * {@inheritDoc}
     */
    public function create(Channel $channel): ShippingMethodInterface
    {
        /** @var FixedProductSettings $transport */
        $transport = $channel->getTransport();

        return new FixedProductMethod(
            $this->identifierGenerator->generateIdentifier($channel),
            (string)$this->localizationHelper->getLocalizedValue($transport->getLabels()),
            $this->integrationIconProvider->getIcon($channel),
            $channel->isEnabled(),
            $this->roundingService,
            $this->shippingCostProvider
        );
    }
}
