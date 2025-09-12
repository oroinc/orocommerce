<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;

/**
 * Provides a virtual relation for the PaymentStatus entity to display the payment status of an entity.
 */
class PaymentStatusVirtualRelationProvider implements VirtualRelationProviderInterface
{
    public const string VIRTUAL_RELATION_NAME = 'paymentStatus';

    public function __construct(private readonly string $entityClass)
    {
    }

    #[\Override]
    public function isVirtualRelation($className, $fieldName): bool
    {
        return is_a($className, $this->entityClass, true) && $fieldName === self::VIRTUAL_RELATION_NAME;
    }

    #[\Override]
    public function getVirtualRelationQuery($className, $fieldName): array
    {
        $relations = $this->getVirtualRelations($className);

        return isset($relations[$fieldName]) ? $relations[$fieldName]['query'] : [];
    }

    #[\Override]
    public function getVirtualRelations($className): array
    {
        if (!is_a($className, $this->entityClass, true)) {
            return [];
        }

        return [
            self::VIRTUAL_RELATION_NAME => [
                'label' => 'oro.payment.paymentstatus.entity_label',
                'relation_type' => RelationType::ONE_TO_ONE,
                'related_entity_name' => PaymentStatus::class,
                'target_join_alias' => 'paymentStatusEntity',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => PaymentStatus::class,
                                'conditionType' => Join::WITH,
                                'alias' => 'paymentStatusEntity',
                                'condition' => sprintf(
                                    'paymentStatusEntity.entityClass = \'%s\' AND '
                                    . 'paymentStatusEntity.entityIdentifier = entity.id',
                                    $className
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[\Override]
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null): string
    {
        $relations = $this->getVirtualRelations($className);

        return isset($relations[$fieldName]) ? $relations[$fieldName]['target_join_alias'] : $fieldName;
    }
}
