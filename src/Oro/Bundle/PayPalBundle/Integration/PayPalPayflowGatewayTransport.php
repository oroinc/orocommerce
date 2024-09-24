<?php

namespace Oro\Bundle\PayPalBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * PayPal Payflow Gateway integration transport
 */
class PayPalPayflowGatewayTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    #[\Override]
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return PayPalSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return PayPalSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.paypal.settings.label';
    }
}
