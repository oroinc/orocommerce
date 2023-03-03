<?php

namespace Oro\Bundle\PaymentTermBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Payment term association provider.
 */
class PaymentTermAssociationProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var string */
    private $associationName;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param $className
     * @return string[]
     */
    public function getAssociationNames($className)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($className);
        $mappings = $metadata->getAssociationsByTargetClass(PaymentTerm::class);

        $associationNames = [];
        if ($metadata->hasAssociation($this->getDefaultAssociationName())) {
            $associationNames[$this->getDefaultAssociationName()] = $this->getDefaultAssociationName();
        }

        foreach ($mappings as $mapping) {
            $associationNames[$mapping['fieldName']] = $mapping['fieldName'];
        }

        return array_values($associationNames);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    public function getTargetField($className, $fieldName)
    {
        return $this->configProvider->getConfig($className, $fieldName)->get('target_field', false, $fieldName);
    }

    /**
     * @param object $entity
     * @param string $associationName
     * @return PaymentTerm|null
     */
    public function getPaymentTerm($entity, $associationName = null)
    {
        try {
            return $this->propertyAccessor->getValue($entity, $associationName ?: $this->getDefaultAssociationName());
        } catch (NoSuchPropertyException $e) {
        }

        return null;
    }

    /**
     * @param object $entity
     * @param PaymentTerm $paymentTerm
     * @param string|null $associationName
     */
    public function setPaymentTerm($entity, PaymentTerm $paymentTerm, $associationName = null)
    {
        $this->propertyAccessor->setValue(
            $entity,
            $associationName ?: $this->getDefaultAssociationName(),
            $paymentTerm
        );
    }

    /**
     * @return string
     */
    public function getDefaultAssociationName()
    {
        if (!$this->associationName) {
            $this->associationName = ExtendHelper::buildAssociationName(PaymentTerm::class);
        }

        return $this->associationName;
    }
}
