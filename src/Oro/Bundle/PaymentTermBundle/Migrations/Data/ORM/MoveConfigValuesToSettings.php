<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PaymentBundle\Migrations\Data\ORM\AbstractMoveConfigValuesToSettings;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config\ChannelFactory;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config\PaymentTermConfigFactory;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config\PaymentTermConfigToSettingsConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoveConfigValuesToSettings extends AbstractMoveConfigValuesToSettings
{
    const SECTION_NAME = 'oro_payment_term';

    /**
     * @var ChannelFactory
     */
    protected $channelFactory;

    /**
     * @var PaymentTermConfigFactory
     */
    protected $configFactory;

    /**
     * @var PaymentTermConfigToSettingsConverter
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

        $this->methodIdentifierGenerator =
            $container->get('oro_payment_term.config.integration_method_identifier_generator');
        $this->channelFactory = $this->createChannelFactory($container);
        $this->configFactory = $this->createConfigFactory($container);
        $this->configToSettingsConverter = new PaymentTermConfigToSettingsConverter();
    }

    /**
     * {@inheritDoc}
     */
    protected function moveConfigFromSystemConfigToIntegration(
        ObjectManager $manager,
        OrganizationInterface $organization
    ) {
        $paymentTermSystemConfig = $this->configFactory->createPaymentTermConfig();

        $channel = $this->channelFactory->createChannel(
            $organization,
            $this->configToSettingsConverter->convert($paymentTermSystemConfig),
            $paymentTermSystemConfig->isAllRequiredFieldsSet()
        );

        $manager->persist($channel);
        $manager->flush();

        $this->dispatchPaymentMethodRenamingEvent($channel);

        $manager->flush();
    }

    protected function dispatchPaymentMethodRenamingEvent(Channel $channel)
    {
        $this->dispatcher->dispatch(
            PaymentTermChannelType::TYPE,
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
            $container->get('oro_payment_term.integration.channel'),
            $container->get('translator')
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PaymentTermConfigFactory
     */
    protected function createConfigFactory(ContainerInterface $container)
    {
        return new PaymentTermConfigFactory(
            $container->get('oro_config.manager')
        );
    }
}
