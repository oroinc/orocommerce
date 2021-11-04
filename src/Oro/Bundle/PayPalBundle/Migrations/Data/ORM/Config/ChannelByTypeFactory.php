<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayChannelType;
use Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProChannelType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides functionality to create a Channel
 */
class ChannelByTypeFactory
{
    /**
     * @var PayPalPaymentsProChannelType
     */
    private $paymentsProChannelType;

    /**
     * @var PayPalPayflowGatewayChannelType
     */
    private $payflowGatewayChannelType;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        PayPalPaymentsProChannelType $paymentsProChannelType,
        PayPalPayflowGatewayChannelType $payflowGatewayChannelType,
        TranslatorInterface $translator
    ) {
        $this->paymentsProChannelType = $paymentsProChannelType;
        $this->payflowGatewayChannelType = $payflowGatewayChannelType;
        $this->translator = $translator;
    }

    /**
     * @param OrganizationInterface $organization
     * @param PayPalSettings        $settings
     * @param bool                  $isEnabled
     *
     * @return Channel
     */
    public function createPaymentProChannel(OrganizationInterface $organization, PayPalSettings $settings, $isEnabled)
    {
        return $this->createChannel(
            $organization,
            $this->paymentsProChannelType,
            PayPalPaymentsProChannelType::TYPE,
            $settings,
            $isEnabled
        );
    }

    /**
     * @param OrganizationInterface $organization
     * @param PayPalSettings        $settings
     * @param bool                  $isEnabled
     *
     * @return Channel
     */
    public function createPayflowGatewayChannel(
        OrganizationInterface $organization,
        PayPalSettings $settings,
        $isEnabled
    ) {
        return $this->createChannel(
            $organization,
            $this->payflowGatewayChannelType,
            PayPalPayflowGatewayChannelType::TYPE,
            $settings,
            $isEnabled
        );
    }

    /**
     * @param OrganizationInterface $organization
     * @param ChannelInterface      $channel
     * @param string                $type
     * @param PayPalSettings        $settings
     * @param bool                  $isEnabled
     *
     * @return Channel
     */
    private function createChannel(
        OrganizationInterface $organization,
        ChannelInterface $channel,
        $type,
        PayPalSettings $settings,
        $isEnabled
    ) {
        $name = $this->getChannelTypeTranslatedLabel($channel);

        $channel = new Channel();
        $channel->setType($type)
            ->setName($name)
            ->setEnabled($isEnabled)
            ->setOrganization($organization)
            ->setTransport($settings);

        return $channel;
    }

    /**
     * @param ChannelInterface $channel
     *
     * @return string
     */
    private function getChannelTypeTranslatedLabel(ChannelInterface $channel)
    {
        return $this->translator->trans((string) $channel->getLabel());
    }
}
