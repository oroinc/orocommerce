<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Incapsulates logic to prepare dropdown values for line items grouping field selection.
 */
class FieldsOptionsProvider
{
    public const DEFAULT_VALUE = 'product.owner';

    protected ConfigManager $configManager;
    protected ManagerRegistry $managerRegistry;

    protected array $availableFieldsForGrouping = [
        'product' => [
            'id',
            'owner',
            'category',
            'brand'
        ],
        'parentProduct'
    ];

    public function __construct(ConfigManager $configManager, ManagerRegistry $managerRegistry)
    {
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return array [
     *                   'oro.product.entity_label' => [
     *                        'oro.product.id.label' => 'product.id',
     *                        'oro.product.id.owner' => 'product.owner'
     *                        .....
     *                    ]
     *                    ....
     *                ]
     */
    public function getAvailableFieldsForGroupingFormOptions(): array
    {
        $groupedOptions = [];
        foreach ($this->availableFieldsForGrouping as $field => $fieldName) {
            if (is_array($fieldName)) {
                $entityClass = $this->getAssociationFieldEntity($field);
                $groupedOptions[$this->getFieldTitle($entityClass)]
                    = $this->convertFields($entityClass, $field, $fieldName);
            } else {
                $title = $this->getFieldTitle(CheckoutLineItem::class, $fieldName);
                $groupedOptions[$this->getFieldTitle(CheckoutLineItem::class)][$title]
                    = $fieldName;
            }
        }

        return $groupedOptions;
    }

    private function convertFields(string $className, string $parentFieldName, array $fieldNames): array
    {
        $fieldValues = [];

        foreach ($fieldNames as $fieldName) {
            $fullKey = $parentFieldName . '.' . $fieldName;
            $fieldValues[$this->getFieldTitle($className, $fieldName)] = $fullKey;
        }

        return $fieldValues;
    }

    private function getFieldTitle(string $className, $fieldName = null): string
    {
        if (null === $fieldName) {
            $entityConfig = $this->configManager->getEntityConfig('entity', $className);
            return $entityConfig->get('label');
        }

        $fieldConfig = $this->configManager->getFieldConfig('entity', $className, $fieldName);
        return $fieldConfig->get('label');
    }

    private function getAssociationFieldEntity(string $fieldName): string
    {
        $em = $this->managerRegistry->getManagerForClass(OrderLineItem::class);
        $metadata = $em->getClassMetadata(OrderLineItem::class);
        return $metadata->getAssociationTargetClass($fieldName);
    }
}
