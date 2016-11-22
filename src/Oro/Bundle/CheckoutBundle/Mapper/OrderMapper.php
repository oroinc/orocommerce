<?php

namespace Oro\Bundle\CheckoutBundle\Mapper;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class OrderMapper implements MapperInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var EntityFieldProvider */
    private $entityFieldProvider;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(EntityFieldProvider $entityFieldProvider, PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /** {@inheritdoc} */
    public function map(Checkout $checkout, array $data = [])
    {
        $order = new Order();
        $data = array_merge($this->getData($checkout), $data);

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

        $this->assignData($order, $data);

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
     */
    protected function assignData(Order $entity, array $data)
    {
        foreach ($data as $name => $value) {
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
        $fields = $this->entityFieldProvider->getFields(Order::class, true, true, false, true, true, false);

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
}
