<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusVirtualRelationProvider;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusVirtualRelationProviderTest extends TestCase
{
    private PaymentStatusVirtualRelationProvider $provider;
    private string $entityClass;

    protected function setUp(): void
    {
        $this->entityClass = Order::class;
        $this->provider = new PaymentStatusVirtualRelationProvider($this->entityClass);
    }

    public function testIsVirtualRelationWithValidEntityAndField(): void
    {
        $result = $this->provider->isVirtualRelation($this->entityClass, 'paymentStatus');

        self::assertTrue($result);
    }

    public function testIsVirtualRelationWithInvalidEntity(): void
    {
        $result = $this->provider->isVirtualRelation('SomeOtherClass', 'paymentStatus');

        self::assertFalse($result);
    }

    public function testIsVirtualRelationWithInvalidField(): void
    {
        $result = $this->provider->isVirtualRelation($this->entityClass, 'invalidField');

        self::assertFalse($result);
    }

    public function testIsVirtualRelationWithBothInvalid(): void
    {
        $result = $this->provider->isVirtualRelation('SomeOtherClass', 'invalidField');

        self::assertFalse($result);
    }

    public function testGetVirtualRelationQueryWithValidInput(): void
    {
        $result = $this->provider->getVirtualRelationQuery($this->entityClass, 'paymentStatus');

        $expectedQuery = [
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

    public function testGetVirtualRelationQueryWithInvalidField(): void
    {
        $result = $this->provider->getVirtualRelationQuery($this->entityClass, 'invalidField');

        self::assertEquals([], $result);
    }

    public function testGetVirtualRelationQueryWithInvalidEntity(): void
    {
        $result = $this->provider->getVirtualRelationQuery('SomeOtherClass', 'paymentStatus');

        self::assertEquals([], $result);
    }

    public function testGetVirtualRelationsWithValidEntity(): void
    {
        $result = $this->provider->getVirtualRelations($this->entityClass);

        self::assertArrayHasKey('paymentStatus', $result);

        $paymentStatusRelation = $result['paymentStatus'];
        self::assertEquals('oro.payment.paymentstatus.entity_label', $paymentStatusRelation['label']);
        self::assertEquals(RelationType::ONE_TO_ONE, $paymentStatusRelation['relation_type']);
        self::assertEquals(PaymentStatus::class, $paymentStatusRelation['related_entity_name']);
        self::assertEquals('paymentStatusEntity', $paymentStatusRelation['target_join_alias']);

        // Verify query structure
        self::assertArrayHasKey('query', $paymentStatusRelation);
        $query = $paymentStatusRelation['query'];
        self::assertArrayHasKey('join', $query);
        self::assertArrayHasKey('left', $query['join']);
        self::assertCount(1, $query['join']['left']);

        $leftJoin = $query['join']['left'][0];
        self::assertEquals(PaymentStatus::class, $leftJoin['join']);
        self::assertEquals(Join::WITH, $leftJoin['conditionType']);
        self::assertEquals('paymentStatusEntity', $leftJoin['alias']);
        self::assertEquals(
            sprintf(
                'paymentStatusEntity.entityClass = \'%s\' AND paymentStatusEntity.entityIdentifier = entity.id',
                $this->entityClass
            ),
            $leftJoin['condition']
        );
    }

    public function testGetVirtualRelationsWithInvalidEntity(): void
    {
        $result = $this->provider->getVirtualRelations('SomeOtherClass');

        self::assertEquals([], $result);
    }

    public function testGetTargetJoinAliasWithValidInput(): void
    {
        $result = $this->provider->getTargetJoinAlias($this->entityClass, 'paymentStatus');

        self::assertEquals('paymentStatusEntity', $result);
    }

    public function testGetTargetJoinAliasWithSelectFieldName(): void
    {
        $result = $this->provider->getTargetJoinAlias($this->entityClass, 'paymentStatus', 'someSelectField');

        self::assertEquals('paymentStatusEntity', $result);
    }

    public function testGetTargetJoinAliasWithInvalidField(): void
    {
        $result = $this->provider->getTargetJoinAlias($this->entityClass, 'invalidField');

        self::assertEquals('invalidField', $result);
    }

    public function testGetTargetJoinAliasWithInvalidEntity(): void
    {
        $result = $this->provider->getTargetJoinAlias('SomeOtherClass', 'paymentStatus');

        self::assertEquals('paymentStatus', $result);
    }
}
