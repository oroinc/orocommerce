<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PurchasePaymentAction extends AbstractPaymentAction
{
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
     * @param TransactionPaymentContextFactory $paymentContextFactory
     * @param ApruveOrderBuilderFactoryInterface $apruveOrderBuilderFactory
     * @param OrderSecureHashGeneratorInterface $orderSecureHashGenerator
     */
    public function __construct(
        TransactionPaymentContextFactory $paymentContextFactory,
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
}
