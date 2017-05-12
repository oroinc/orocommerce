<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Order\ApruveOrderFromPaymentContextFactory;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Order\ApruveOrderFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PurchasePaymentAction extends AbstractPaymentAction implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const NAME = PaymentMethodInterface::PURCHASE;

    /**
     * @var OrderSecureHashGeneratorInterface
     */
    private $orderSecureHashGenerator;

    /**
     * @var ApruveOrderFromPaymentContextFactory
     */
    private $apruveOrderFromPaymentContextFactory;

    /**
     * @param TransactionPaymentContextFactoryInterface     $paymentContextFactory
     * @param ApruveOrderFromPaymentContextFactoryInterface $apruveOrderFromPaymentContextFactory
     * @param OrderSecureHashGeneratorInterface             $orderSecureHashGenerator
     */
    public function __construct(
        TransactionPaymentContextFactoryInterface $paymentContextFactory,
        ApruveOrderFromPaymentContextFactoryInterface $apruveOrderFromPaymentContextFactory,
        OrderSecureHashGeneratorInterface $orderSecureHashGenerator
    ) {
        parent::__construct($paymentContextFactory);

        $this->orderSecureHashGenerator = $orderSecureHashGenerator;
        $this->apruveOrderFromPaymentContextFactory = $apruveOrderFromPaymentContextFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentContext = $this->paymentContextFactory->create($paymentTransaction);
        if ($paymentContext === null) {
            $this->logNoPaymentContext($paymentTransaction->getId());

            return [];
        }

        $apruveOrder = $this->apruveOrderFromPaymentContextFactory
            ->createFromPaymentContext($paymentContext, $apruveConfig);

        $secureHash = $this->orderSecureHashGenerator->generate($apruveOrder, $apruveConfig->getApiKey());

        $requestData = [
            'apruveOrder' => $apruveOrder->getData(),
            'apruveOrderSecureHash' => $secureHash,
        ];

        $paymentTransaction->setRequest($requestData);

        // Transaction is not finished yet.
        $paymentTransaction->setSuccessful(false);

        // Transaction should be authorized by end user.
        $paymentTransaction->setActive(true);

        return $requestData;
    }

    /**
     * @param int $transactionId
     */
    private function logNoPaymentContext($transactionId)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Payment context was not created from given transaction (%d)',
                $transactionId
            );
            $this->logger->error($msg);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
