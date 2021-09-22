<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides functionality to create a Channel
 */
class ChannelFactory
{
    /**
     * @var MoneyOrderChannelType
     */
    private $channelType;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        MoneyOrderChannelType $moneyOrderChannelType,
        TranslatorInterface $translator
    ) {
        $this->channelType = $moneyOrderChannelType;
        $this->translator = $translator;
    }

    /**
     * @param OrganizationInterface $organization
     * @param MoneyOrderSettings    $settings
     * @param bool                  $isEnabled
     *
     * @return Channel
     */
    public function createChannel(
        OrganizationInterface $organization,
        MoneyOrderSettings $settings,
        $isEnabled
    ) {
        $name = $this->getChannelTypeTranslatedLabel($this->channelType);

        $channel = new Channel();
        $channel->setType(MoneyOrderChannelType::TYPE)
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
