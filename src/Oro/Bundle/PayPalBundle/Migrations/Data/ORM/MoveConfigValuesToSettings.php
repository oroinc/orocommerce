<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PaymentBundle\Migrations\Data\ORM\AbstractMoveConfigValuesToSettings;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\ChannelByTypeFactory;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PaymentMethodIdentifierByChannelProvider;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PayPalConfigFactory;
use Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config\PayPalConfigToSettingsConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoveConfigValuesToSettings extends AbstractMoveConfigValuesToSettings
{
    const SECTION_NAME = 'oro_paypal';

    const PAYFLOW_GATEWAY_TYPE = 'payflow_gateway';
    const PAYFLOW_GATEWAY_EXPRESS_CHECKOUT_TYPE = 'payflow_express_checkout';
    const PAYMENTS_PRO_TYPE = 'paypal_payments_pro';
    const PAYMENTS_PRO_EXPRESS_CHECKOUT_TYPE = 'paypal_payments_pro_express_checkout';

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
     * @var PaymentMethodIdentifierByChannelProvider
     */
    protected $paymentMethodIdentifierByChannelProvider;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->channelFromPayPalConfigFactory = $this->createChannelFromPayPalConfigFactory($container);
        $this->payPalConfigFactory = $this->createPayPalConfigFactory($container);
        $this->payPalConfigToSettingsConverter = new PayPalConfigToSettingsConverter();
        $this->paymentMethodIdentifierByChannelProvider =
            $this->createPaymentMethodIdentifierByChannelProvider($container);
    }

    /**
     * {@inheritDoc}
     */
    protected function moveConfigFromSystemConfigToIntegration(
        ObjectManager $manager,
        OrganizationInterface $organization
    ) {
        $paymentsProSystemConfig = $this->payPalConfigFactory->createPaymentsProConfig();
        $payflowGatewaySystemConfig = $this->payPalConfigFactory->createPayflowGatewayConfig();

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
