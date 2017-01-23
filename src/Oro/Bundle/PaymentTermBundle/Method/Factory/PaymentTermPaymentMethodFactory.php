<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class PaymentTermPaymentMethodFactory implements PaymentTermPaymentMethodFactoryInterface
{
    use LoggerAwareTrait;

    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var PaymentTermAssociationProvider
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PaymentTermConfigInterface $config)
    {
        $paymentMethod = new PaymentTerm(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper,
            $config
        );
        $paymentMethod->setLogger($this->logger);

        return $paymentMethod;
    }
}
