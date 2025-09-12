<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus;

/**
 * Contains the payment statuses available out-of-the-box.
 *
 * These statuses are used to denote the payment status for an entity.
 * In order to get the current payment status - use {@see PaymentStatusManager}.
 */
class PaymentStatuses
{
    public const string PAID_IN_FULL = 'full';
    public const string PAID_PARTIALLY = 'partially';
    public const string INVOICED = 'invoiced';
    public const string AUTHORIZED = 'authorized';
    public const string AUTHORIZED_PARTIALLY = 'authorized_partially';
    public const string DECLINED = 'declined';
    public const string PENDING = 'pending';
    public const string CANCELED = 'canceled';
    public const string CANCELED_PARTIALLY = 'canceled_partially';
    public const string REFUNDED = 'refunded';
    public const string REFUNDED_PARTIALLY = 'refunded_partially';

    /**
     * @return array<string> List of payment statuses available out-of-the-box.
     */
    public static function getAllPaymentStatuses(): array
    {
        return [
            self::PAID_IN_FULL,
            self::PAID_PARTIALLY,
            self::INVOICED,
            self::AUTHORIZED,
            self::AUTHORIZED_PARTIALLY,
            self::DECLINED,
            self::PENDING,
            self::CANCELED,
            self::CANCELED_PARTIALLY,
            self::REFUNDED,
            self::REFUNDED_PARTIALLY,
        ];
    }
}
