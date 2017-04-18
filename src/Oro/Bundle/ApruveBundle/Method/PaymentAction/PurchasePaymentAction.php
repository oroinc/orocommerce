<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Psr\Log\LoggerAwareTrait;

class PurchasePaymentAction extends AbstractPaymentAction
{
    use LoggerAwareTrait;

    const NAME = 'purchase';

    /**
     * @var ApruveOrderBuilderFactoryInterface
     */
    private $apruveOrderBuilderFactory;

    /**
     * @var OrderSecureHashGeneratorInterface
     */
    private $orderSecureHashGenerator;

    /**
     * @param TransactionPaymentContextFactoryInterface $paymentContextFactory
     * @param ApruveOrderBuilderFactoryInterface $apruveOrderBuilderFactory
     * @param OrderSecureHashGeneratorInterface $orderSecureHashGenerator
     */
    public function __construct(
        TransactionPaymentContextFactoryInterface $paymentContextFactory,
        ApruveOrderBuilderFactoryInterface $apruveOrderBuilderFactory,
        OrderSecureHashGeneratorInterface $orderSecureHashGenerator
    ) {
        parent::__construct($paymentContextFactory);

        $this->apruveOrderBuilderFactory = $apruveOrderBuilderFactory;
        $this->orderSecureHashGenerator = $orderSecureHashGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentContext = $this->paymentContextFactory->create($paymentTransaction);
        if ($paymentContext === null) {
            $this->logNoPaymentContext($paymentTransaction->getId());

            return [];
        }

        $apruveOrderBuilder = $this->apruveOrderBuilderFactory->create($paymentContext, $apruveConfig);
        $apruveOrderBuilder
            ->setFinalizeOnCreate(true)
            ->setInvoiceOnCreate(false);

        $apruveOrder = $apruveOrderBuilder->getResult();
        $secureHash = $this->orderSecureHashGenerator->generate($apruveOrder, $apruveConfig);

        $apruveOrderData = $apruveOrder->getData();

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $transactionOptions['apruveOrder'] = $apruveOrderData;
        $paymentTransaction->setTransactionOptions($transactionOptions);

        // Transaction is not finished yet.
        $paymentTransaction->setSuccessful(false);

        // Transaction should be authorized by end user.
        $paymentTransaction->setActive(true);

        return [
            'apruveOrder' => $apruveOrderData,
            'apruveOrderSecureHash' => $secureHash,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param int $transactionId
     */
    protected function logNoPaymentContext($transactionId)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Payment context was not created from given transaction (%d)',
                $transactionId
            );
            $this->logger->error($msg);
        }
    }
}
