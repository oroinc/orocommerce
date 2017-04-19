<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class PaymentTerm implements PaymentMethodInterface
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
     * @var PaymentTermConfigInterface
     */
    protected $config;

    /**
     * @param PaymentTermProvider            $paymentTermProvider
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param DoctrineHelper                 $doctrineHelper
     * @param PaymentTermConfigInterface     $config
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        DoctrineHelper $doctrineHelper,
        PaymentTermConfigInterface $config
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if (!$paymentTerm) {
            return [];
        }

        try {
            $this->paymentTermAssociationProvider->setPaymentTerm($entity, $paymentTerm);
            $this->doctrineHelper->getEntityManager($entity)->flush($entity);
        } catch (NoSuchPropertyException $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'Property association {paymentTermClass} not found for entity {entityClass}',
                    [
                        'exception' => $e,
                        'paymentTermClass' => get_class($paymentTerm),
                        'entityClass' => get_class($entity),
                    ]
                );
            }

            return [];
        }

        $paymentTransaction
            ->setSuccessful(true)
            ->setActive(false);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        if ($context->getCustomer()) {
            return (bool)$this->paymentTermProvider->getPaymentTerm($context->getCustomer());
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}
