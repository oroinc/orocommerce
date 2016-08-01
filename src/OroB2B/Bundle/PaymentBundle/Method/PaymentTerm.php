<?php
namespace OroB2B\Bundle\PaymentBundle\Method;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Method\Config\PaymentTermConfigInterface;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTerm implements PaymentMethodInterface
{
    const TYPE = 'payment_term';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PaymentTermConfigInterface */
    protected $config;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PaymentTermConfigInterface $config
     * @param PropertyAccessor $propertyAccessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermConfigInterface $config,
        PropertyAccessor $propertyAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->config = $config;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
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
            $this->propertyAccessor->setValue($entity, 'paymentTerm', $paymentTerm);
            $this->doctrineHelper->getEntityManager($entity)->flush($entity);
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        $paymentTransaction
            ->setSuccessful(true)
            ->setActive(false);

        return [];
    }

    /** {@inheritdoc} */
    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function isApplicable(array $context = [])
    {
        return $this->config->isCountryApplicable($context)
            && (bool)$this->paymentTermProvider->getCurrentPaymentTerm()
            && $this->config->isCurrencyApplicable($context);
    }

    /** {@inheritdoc} */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}
