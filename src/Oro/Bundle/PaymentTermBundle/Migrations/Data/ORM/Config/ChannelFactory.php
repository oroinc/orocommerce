<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides functionality to create a Channel
 */
class ChannelFactory
{
    /**
     * @var PaymentTermChannelType
     */
    private $channelType;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        PaymentTermChannelType $paymentTermChannelType,
        TranslatorInterface $translator
    ) {
        $this->channelType = $paymentTermChannelType;
        $this->translator = $translator;
    }

    /**
     * @param OrganizationInterface $organization
     * @param PaymentTermSettings   $settings
     * @param                       $isEnabled
     *
     * @return Channel
     */
    public function createChannel(
        OrganizationInterface $organization,
        PaymentTermSettings $settings,
        $isEnabled
    ) {
        $name = $this->getChannelTypeTranslatedLabel($this->channelType);

        $channel = new Channel();
        $channel->setType(PaymentTermChannelType::TYPE)
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
