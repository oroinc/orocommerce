<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayChannelType;
use Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProChannelType;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @param PayPalPaymentsProChannelType    $paymentsProChannelType
     * @param PayPalPayflowGatewayChannelType $payflowGatewayChannelType
     * @param TranslatorInterface             $translator
     */
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
     * @param Organization   $organization
     * @param PayPalSettings $settings
     * @param bool           $isEnabled
     *
     * @return Channel
     */
    public function createPaymentProChannel(Organization $organization, PayPalSettings $settings, $isEnabled)
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
     * @param Organization   $organization
     * @param PayPalSettings $settings
     * @param bool           $isEnabled
     *
     * @return Channel
     */
    public function createPayflowGatewayChannel(Organization $organization, PayPalSettings $settings, $isEnabled)
    {
        return $this->createChannel(
            $organization,
            $this->payflowGatewayChannelType,
            PayPalPayflowGatewayChannelType::TYPE,
            $settings,
            $isEnabled
        );
    }

    /**
     * @param Organization     $organization
     * @param ChannelInterface $channel
     * @param string           $type
     * @param PayPalSettings   $settings
     * @param bool             $isEnabled
     *
     * @return Channel
     */
    private function createChannel(
        Organization $organization,
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
        return $this->translator->trans($channel->getLabel());
    }
}
