<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusLabelVirtualFieldProvider;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusLabelVirtualFieldProviderTest extends TestCase
{
    private PaymentStatusLabelVirtualFieldProvider $provider;
    private string $entityClass;

    protected function setUp(): void
    {
        $this->entityClass = Order::class;
        $this->provider = new PaymentStatusLabelVirtualFieldProvider($this->entityClass);
    }

    public function testIsVirtualFieldWithNonPaymentStatusClass(): void
    {
        $result = $this->provider->isVirtualField(
            $this->entityClass,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        self::assertTrue($result);
    }

    public function testIsVirtualFieldWithPaymentStatusClass(): void
    {
        $paymentStatusProvider = new PaymentStatusLabelVirtualFieldProvider(PaymentStatus::class);

        $result = $paymentStatusProvider->isVirtualField(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        self::assertTrue($result);
    }

    public function testIsVirtualFieldWithInvalidClass(): void
    {
        $result = $this->provider->isVirtualField(
            \stdClass::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        self::assertFalse($result);
    }

    public function testIsVirtualFieldWithInvalidFieldName(): void
    {
        $result = $this->provider->isVirtualField(
            $this->entityClass,
            'invalidFieldName'
        );

        self::assertFalse($result);
    }

    public function testIsVirtualFieldWithBothInvalid(): void
    {
        $result = $this->provider->isVirtualField(
            \stdClass::class,
            'invalidFieldName'
        );

        self::assertFalse($result);
    }

    public function testGetVirtualFieldQueryWithNonPaymentStatusClass(): void
    {
        $result = $this->provider->getVirtualFieldQuery(
            $this->entityClass,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        $expectedQuery = [
            'select' => [
                'expr' => 'paymentStatusEntity.paymentStatus',
                'label' => 'oro.payment.paymentstatus.payment_status_label.label',
                'return_type' => 'string',
            ],
            'join' => [
                'left' => [
                    [
                        'join' => PaymentStatus::class,
                        'conditionType' => Join::WITH,
                        'alias' => 'paymentStatusEntity',
                        'condition' => sprintf(
                            'paymentStatusEntity.entityClass = \'%s\' AND '
                            . 'paymentStatusEntity.entityIdentifier = entity.id',
                            $this->entityClass
                        ),
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedQuery, $result);
    }

    public function testGetVirtualFieldQueryWithPaymentStatusClass(): void
    {
        $paymentStatusProvider = new PaymentStatusLabelVirtualFieldProvider(PaymentStatus::class);

        $result = $paymentStatusProvider->getVirtualFieldQuery(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        $expectedQuery = [
            'select' => [
                'expr' => 'entity.paymentStatus',
                'label' => 'oro.payment.paymentstatus.payment_status_label.label',
                'return_type' => 'string',
            ],
        ];

        self::assertEquals($expectedQuery, $result);
    }

    public function testGetVirtualFieldQueryWithInvalidClass(): void
    {
        $result = $this->provider->getVirtualFieldQuery(
            \stdClass::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME
        );

        self::assertEquals([], $result);
    }

    public function testGetVirtualFieldQueryWithInvalidFieldName(): void
    {
        $result = $this->provider->getVirtualFieldQuery(
            $this->entityClass,
            'invalidFieldName'
        );

        self::assertEquals([], $result);
    }

    public function testGetVirtualFieldsWithNonPaymentStatusClass(): void
    {
        $result = $this->provider->getVirtualFields($this->entityClass);

        self::assertEquals([PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME], $result);
    }

    public function testGetVirtualFieldsWithPaymentStatusClass(): void
    {
        $paymentStatusProvider = new PaymentStatusLabelVirtualFieldProvider(PaymentStatus::class);

        $result = $paymentStatusProvider->getVirtualFields(PaymentStatus::class);

        self::assertEquals([PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME], $result);
    }

    public function testGetVirtualFieldsWithInvalidClass(): void
    {
        $result = $this->provider->getVirtualFields(\stdClass::class);

        self::assertEquals([], $result);
    }
}
