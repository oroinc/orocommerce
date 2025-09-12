<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;

/**
 * Provides a virtual field for the PaymentStatus entity to display a human-readable label for the payment status.
 * The column options for this virtual field are defined in the {@link PaymentStatusLabelColumnOptionsGuesser}.
 */
class PaymentStatusLabelVirtualFieldProvider implements VirtualFieldProviderInterface
{
    public const string VIRTUAL_FIELD_NAME = 'paymentStatusLabel';

    public function __construct(private readonly string $entityClass)
    {
    }

    #[\Override]
    public function isVirtualField($className, $fieldName): bool
    {
        return is_a($className, $this->entityClass, true) && $fieldName === self::VIRTUAL_FIELD_NAME;
    }

    #[\Override]
    public function getVirtualFieldQuery($className, $fieldName): array
    {
        if (!$this->isVirtualField($className, $fieldName)) {
            return [];
        }

        if (!is_a($className, PaymentStatus::class, true)) {
            $query['select'] = [
                'expr' => 'paymentStatusEntity.paymentStatus',
                'label' => 'oro.payment.paymentstatus.payment_status_label.label',
                'return_type' => 'string',
            ];
            $query['join'] = [
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
            ];
        } else {
            $query['select'] = [
                'expr' => 'entity.paymentStatus',
                'label' => 'oro.payment.paymentstatus.payment_status_label.label',
                'return_type' => 'string',
            ];
        }

        return $query;
    }

    #[\Override]
    public function getVirtualFields($className): array
    {
        return is_a($className, $this->entityClass, true) ? [self::VIRTUAL_FIELD_NAME] : [];
    }
}
