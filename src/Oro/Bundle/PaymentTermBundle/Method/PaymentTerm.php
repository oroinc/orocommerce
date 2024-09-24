<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodWithPostponedCaptureInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Implements Payment Term payment method
 */
class PaymentTerm implements PaymentMethodWithPostponedCaptureInterface
{
    use LoggerAwareTrait;

    /**
     * @var PaymentTermProviderInterface
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

    public function __construct(
        PaymentTermProviderInterface $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        DoctrineHelper $doctrineHelper,
        PaymentTermConfigInterface $config
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->config = $config;
    }

    #[\Override]
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" payment method "%s" action is not supported', $this->getIdentifier(), $action)
            );
        }

        return $this->$action($paymentTransaction);
    }

    protected function assignPaymentTerm(PaymentTransaction $paymentTransaction): bool
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return false;
        }

        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if (!$paymentTerm) {
            return false;
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

            return false;
        }

        return true;
    }

    #[\Override]
    public function capture(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction(self::CAPTURE)
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
    }

    #[\Override]
    public function getSourceAction(): string
    {
        return self::PENDING;
    }

    #[\Override]
    public function useSourcePaymentTransaction(): bool
    {
        return true;
    }

    #[\Override]
    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        $assigned = $this->assignPaymentTerm($paymentTransaction);

        $paymentTransaction
            ->setAction($this->getSourceAction())
            ->setSuccessful($assigned)
            ->setActive($assigned);

        return ['successful' => $assigned];
    }

    #[\Override]
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    #[\Override]
    public function isApplicable(PaymentContextInterface $context)
    {
        if ($context->getCustomer()) {
            return (bool)$this->paymentTermProvider->getPaymentTerm($context->getCustomer());
        }

        return false;
    }

    #[\Override]
    public function supports($actionName)
    {
        return \in_array($actionName, [self::PURCHASE, self::CAPTURE], true);
    }
}
