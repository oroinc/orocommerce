<?php

namespace Oro\Bundle\CheckoutBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Maps data from Checkout to Order
 */
class OrderMapper implements MapperInterface
{
    public function __construct(
        private FieldHelper $entityFieldHelper,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    #[\Override]
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
                    'sourceEntityClass' => ClassUtils::getRealClass($sourceEntity->getSourceDocument()),
                ]
            );
        }

        $this->assignData($order, $data, $skipped);

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
            if (str_contains($field, ':')) {// Bypass relations in form ClassName::fieldName.
                continue;
            }

            // Skip createdAt field to allow Order entity to set it to the current datetime
            if ($field === 'createdAt') {
                continue;
            }

            try {
                $value = $this->propertyAccessor->getValue($entity, $field);
                $result[$field] = $value;
            } catch (NoSuchPropertyException $e) {
            }
        }

        return $result;
    }

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
        $fields = $this->entityFieldHelper->getEntityFields(
            Order::class,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
        );

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
