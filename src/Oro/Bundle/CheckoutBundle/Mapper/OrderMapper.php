<?php

namespace Oro\Bundle\CheckoutBundle\Mapper;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class OrderMapper implements MapperInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var EntityFieldProvider */
    private $entityFieldProvider;

    /** @var FieldHelper */
    private $entityFieldHelper;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param PropertyAccessorInterface $propertyAccessor
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     */
    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        PropertyAccessorInterface $propertyAccessor,
        PaymentTermAssociationProvider $paymentTermAssociationProvider
    ) {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
    }

    /**
     * @param FieldHelper $entityFieldHelper
     */
    public function setEntityFieldHelper(FieldHelper $entityFieldHelper)
    {
        $this->entityFieldHelper = $entityFieldHelper;
    }

    /** {@inheritdoc} */
    public function map(Checkout $checkout, array $data = [], array $skipped = [])
    {
        $order = new Order();
        $data = array_merge($this->getData($checkout), $data);
        if ($checkout->getShippingCost()) {
            $data = array_merge($data, ['estimatedShippingCostAmount' => $checkout->getShippingCost()->getValue()]);
        }

        $sourceEntity = $checkout->getSourceEntity();
        if ($sourceEntity) {
            $data = array_merge(
                $data,
                [
                    'sourceEntityId' => $sourceEntity->getSourceDocument()->getId(),
                    'sourceEntityIdentifier' => $sourceEntity->getSourceDocumentIdentifier(),
                    'sourceEntityClass' => ClassUtils::getClass($sourceEntity->getSourceDocument()),
                ]
            );
        }

        $this->assignData($order, $data, $skipped);

        if (!empty($data['paymentTerm'])) {
            $this->assignPaymentTerm($order, $data['paymentTerm']);
        }

        return $order;
    }

    /**
     * @param Checkout $entity
     * @return array
     */
    protected function getData(Checkout $entity)
    {
        $result = [];
        $mapFields = $this->getMapFields();
        foreach ($mapFields as $field) {
            try {
                $value = $this->propertyAccessor->getValue($entity, $field);
                $result[$field] = $value;
            } catch (NoSuchPropertyException $e) {
            }
        }

        return $result;
    }

    /**
     * @param Order $entity
     * @param array $data
     * @param array $skipped
     */
    protected function assignData(Order $entity, array $data, array $skipped)
    {
        foreach ($data as $name => $value) {
            if (!empty($skipped[$name])) {
                continue;
            }
            try {
                $this->propertyAccessor->setValue($entity, $name, $value);
            } catch (NoSuchPropertyException $e) {
            }
        }
    }

    /**
     * @return string[]
     */
    protected function getMapFields()
    {
        $fields = $this->entityFieldHelper->getFields(Order::class, true, true, false, true, true, false);

        $withoutIds = array_filter(
            $fields,
            function ($field) {
                return empty($field['identifier']);
            }
        );

        $fieldsNames = array_column($withoutIds, 'name');
        $staticFields = ['shippingCost'];

        return array_merge($fieldsNames, $staticFields);
    }

    /**
     * @param Order $order
     * @param PaymentTerm $paymentTerm
     */
    protected function assignPaymentTerm(Order $order, PaymentTerm $paymentTerm)
    {
        $this->paymentTermAssociationProvider->setPaymentTerm($order, $paymentTerm);
    }
}
