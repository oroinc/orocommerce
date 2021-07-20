<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config\ChannelFactory;
use Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config\MoneyOrderConfigFactory;
use Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config\MoneyOrderConfigToSettingsConverter;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PaymentBundle\Migrations\Data\ORM\AbstractMoveConfigValuesToSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoveConfigValuesToSettings extends AbstractMoveConfigValuesToSettings
{
    const SECTION_NAME = 'oro_money_order';

    /**
     * @var ChannelFactory
     */
    protected $channelFactory;

    /**
     * @var MoneyOrderConfigFactory
     */
    protected $configFactory;

    /**
     * @var MoneyOrderConfigToSettingsConverter
     */
    protected $configToSettingsConverter;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    protected $methodIdentifierGenerator;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->methodIdentifierGenerator = $container->get('oro_money_order.generator.money_order_config_identifier');
        $this->channelFactory = $this->createChannelFactory($container);
        $this->configFactory = $this->createConfigFactory($container);
        $this->configToSettingsConverter = new MoneyOrderConfigToSettingsConverter();
    }

    /**
     * {@inheritDoc}
     */
    protected function moveConfigFromSystemConfigToIntegration(
        ObjectManager $manager,
        OrganizationInterface $organization
    ) {
        $moneyOrderSystemConfig = $this->configFactory->createMoneyOrderConfig();

        $channel = $this->channelFactory->createChannel(
            $organization,
            $this->configToSettingsConverter->convert($moneyOrderSystemConfig),
            $moneyOrderSystemConfig->isAllRequiredFieldsSet()
        );

        $manager->persist($channel);
        $manager->flush();

        $this->dispatchPaymentMethodRenamingEvent($channel);

        $manager->flush();
    }

    protected function dispatchPaymentMethodRenamingEvent(Channel $channel)
    {
        $this->dispatcher->dispatch(
            MoneyOrderChannelType::TYPE,
            $this->methodIdentifierGenerator->generateIdentifier($channel)
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ChannelFactory
     */
    protected function createChannelFactory(ContainerInterface $container)
    {
        return new ChannelFactory(
            $container->get('oro_money_order.integration.channel'),
            $container->get('translator')
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return MoneyOrderConfigFactory
     */
    protected function createConfigFactory(ContainerInterface $container)
    {
        return new MoneyOrderConfigFactory(
            $container->get('oro_config.manager')
        );
    }
}
