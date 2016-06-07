<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

class PaymentTerm implements PaymentMethodInterface
{
    use ConfigTrait, CountryAwarePaymentMethodTrait, CurrencyAwarePaymentMethodTrait;

    const TYPE = 'payment_term';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

   /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param ConfigManager $configManager
     * @param PropertyAccessor $propertyAccessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        ConfigManager $configManager,
        PropertyAccessor $propertyAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->configManager = $configManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
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
        return $this->getConfigValue(Configuration::PAYMENT_TERM_ENABLED_KEY);
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function isApplicable(array $context = [])
    {
        return $this->isCountryApplicable($context)
            && (bool)$this->paymentTermProvider->getCurrentPaymentTerm()
            && $this->isCurrencyApplicable($context);
    }

    /**
     * @return bool
     */
    protected function getAllowedCountries()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_SELECTED_COUNTRIES_KEY);
    }

    /**
     * @return bool
     */
    protected function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_ALLOWED_COUNTRIES_KEY)
            === Configuration::ALLOWED_COUNTRIES_ALL;
    }

    /** {@inheritdoc} */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }

    /**
     * {@inheritdoc}
     */
    public function completeTransaction(PaymentTransaction $paymentTransaction, array $data)
    {
        throw new \LogicException('Unexpected method call');
    }

    /**
     * @return array
     */
    protected function getAllowedCurrencies()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_ALLOWED_CURRENCIES);
    }
}
