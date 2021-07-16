<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

class PaymentMethodIdentifierByChannelProvider
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $payflowGatewayCreditCardIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $payflowGatewayExpressCheckoutIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $paymentsProCreditCardIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $paymentsProExpressCheckoutIdentifierGenerator;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $payflowGatewayCreditCardIdentifierGenerator,
        IntegrationIdentifierGeneratorInterface $payflowGatewayExpressCheckoutIdentifierGenerator,
        IntegrationIdentifierGeneratorInterface $paymentsProCreditCardIdentifierGenerator,
        IntegrationIdentifierGeneratorInterface $paymentsProExpressCheckoutIdentifierGenerator
    ) {
        $this->payflowGatewayCreditCardIdentifierGenerator = $payflowGatewayCreditCardIdentifierGenerator;
        $this->payflowGatewayExpressCheckoutIdentifierGenerator = $payflowGatewayExpressCheckoutIdentifierGenerator;
        $this->paymentsProCreditCardIdentifierGenerator = $paymentsProCreditCardIdentifierGenerator;
        $this->paymentsProExpressCheckoutIdentifierGenerator = $paymentsProExpressCheckoutIdentifierGenerator;
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    public function getPayflowGatewayCreditCardIdentifier(Channel $channel)
    {
        return $this->payflowGatewayCreditCardIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    public function getPayflowGatewayExpressCheckoutIdentifier(Channel $channel)
    {
        return $this->payflowGatewayExpressCheckoutIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    public function getPaymentsProCreditCardIdentifier(Channel $channel)
    {
        return $this->paymentsProCreditCardIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    public function getPaymentsProExpressCheckoutIdentifier(Channel $channel)
    {
        return $this->paymentsProExpressCheckoutIdentifierGenerator->generateIdentifier($channel);
    }
}
