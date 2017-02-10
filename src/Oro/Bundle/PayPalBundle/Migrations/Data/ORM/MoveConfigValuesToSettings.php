<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEventDispatcherInterface;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\ChannelByTypeFactory;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PaymentMethodIdentifierByChannelProvider;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PayPalConfigToSettingsConverter;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PayPalConfigFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoveConfigValuesToSettings extends AbstractFixture implements ContainerAwareInterface
{
    const SECTION_NAME = 'oro_paypal';

    const PAYFLOW_GATEWAY_TYPE = 'payflow_gateway';
    const PAYFLOW_GATEWAY_EXPRESS_CHECKOUT_TYPE = 'payflow_express_checkout';
    const PAYMENTS_PRO_TYPE = 'paypal_payments_pro';
    const PAYMENTS_PRO_EXPRESS_CHECKOUT_TYPE = 'paypal_payments_pro_express_checkout';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ChannelByTypeFactory
     */
    protected $channelFromPayPalConfigFactory;

    /**
     * @var PayPalConfigFactory
     */
    protected $payPalConfigFactory;

    /**
     * @var PayPalConfigToSettingsConverter
     */
    protected $payPalConfigToSettingsConverter;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var PaymentMethodIdentifierByChannelProvider
     */
    protected $paymentMethodIdentifierByChannelProvider;

    /**
     * @var MethodRenamingEventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->doctrine = $container->get('doctrine');
        $this->channelFromPayPalConfigFactory = $this->createChannelFromPayPalConfigFactory($container);
        $this->payPalConfigFactory = $this->createPayPalConfigFactory($container);
        $this->payPalConfigToSettingsConverter = new PayPalConfigToSettingsConverter();
        $this->installed = $container->hasParameter('installed') && $container->getParameter('installed');
        $this->dispatcher = $container->get('oro_payment.method.event.dispatcher.method_renaming');
        $this->paymentMethodIdentifierByChannelProvider =
            $this->createPaymentMethodIdentifierByChannelProvider($container);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->installed) {
            $this->moveConfigFromSystemConfigToIntegration($manager);
            $this->getConfigValueRepository()->removeBySection(self::SECTION_NAME);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    protected function moveConfigFromSystemConfigToIntegration(ObjectManager $manager)
    {
        $paymentsProSystemConfig = $this->payPalConfigFactory->createPaymentsProConfig();
        $payflowGatewaySystemConfig = $this->payPalConfigFactory->createPayflowGatewayConfig();

        $organization = $this->getOrganization();

        $paymentsProChannel = $this->channelFromPayPalConfigFactory->createPaymentProChannel(
            $organization,
            $this->payPalConfigToSettingsConverter->convert($paymentsProSystemConfig),
            $paymentsProSystemConfig->isAllRequiredFieldsSet()
        );

        $payflowGatewayChannel = $this->channelFromPayPalConfigFactory->createPayflowGatewayChannel(
            $organization,
            $this->payPalConfigToSettingsConverter->convert($payflowGatewaySystemConfig),
            $payflowGatewaySystemConfig->isAllRequiredFieldsSet()
        );

        $manager->persist($paymentsProChannel);
        $manager->persist($payflowGatewayChannel);
        $manager->flush();

        $this->getDispatchPaymentMethodRenamingEvent($paymentsProChannel, $payflowGatewayChannel);

        $manager->flush();
    }

    /**
     * @param Channel $paymentsProChannel
     * @param Channel $payflowGatewayChannel
     */
    protected function getDispatchPaymentMethodRenamingEvent(
        Channel $paymentsProChannel,
        Channel $payflowGatewayChannel
    ) {
        $newNamesByOldNames = [
            self::PAYFLOW_GATEWAY_TYPE => $this->paymentMethodIdentifierByChannelProvider
                ->getPayflowGatewayCreditCardIdentifier($payflowGatewayChannel),
            self::PAYFLOW_GATEWAY_EXPRESS_CHECKOUT_TYPE => $this->paymentMethodIdentifierByChannelProvider
                ->getPayflowGatewayExpressCheckoutIdentifier($payflowGatewayChannel),
            self::PAYMENTS_PRO_TYPE => $this->paymentMethodIdentifierByChannelProvider
                ->getPaymentsProCreditCardIdentifier($paymentsProChannel),
            self::PAYMENTS_PRO_EXPRESS_CHECKOUT_TYPE => $this->paymentMethodIdentifierByChannelProvider
                ->getPaymentsProExpressCheckoutIdentifier($paymentsProChannel),
        ];

        foreach ($newNamesByOldNames as $oldName => $newName) {
            $this->dispatcher->dispatch($oldName, $newName);
        }
    }

    /**
     * @return ConfigValueRepository
     */
    protected function getConfigValueRepository()
    {
        return $this->doctrine->getManagerForClass(ConfigValue::class)->getRepository(ConfigValue::class);
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->doctrine->getRepository(Organization::class)->getFirst();
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ChannelByTypeFactory
     */
    protected function createChannelFromPayPalConfigFactory(ContainerInterface $container)
    {
        return new ChannelByTypeFactory(
            $container->get('oro_paypal.integation.payments_pro.channel'),
            $container->get('oro_paypal.integation.payflow_gateway.channel'),
            $container->get('translator')
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PayPalConfigFactory
     */
    protected function createPayPalConfigFactory(ContainerInterface $container)
    {
        return new PayPalConfigFactory(
            $container->get('oro_paypal.settings.payment_action.provider'),
            $container->get('oro_paypal.settings.card_type.provider'),
            $container->get('oro_config.manager')
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return PaymentMethodIdentifierByChannelProvider
     */
    protected function createPaymentMethodIdentifierByChannelProvider(ContainerInterface $container)
    {
        return new PaymentMethodIdentifierByChannelProvider(
            $container->get('oro_paypal.method.generator.identifier.payflow_gateway.credit_card'),
            $container->get('oro_paypal.method.generator.identifier.payflow_gateway.express_checkout'),
            $container->get('oro_paypal.method.generator.identifier.payments_pro.credit_card'),
            $container->get('oro_paypal.method.generator.identifier.payments_pro.express_checkout')
        );
    }
}
