<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\OrderDatagridListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

class OrderDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var OrderDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new OrderDatagridListener($this->doctrineHelper);
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testOnResultAfter(array $returnResult, array $expectation)
    {
        $event = $this->createMock(OrmResultAfter::class);
        $recordId = 1;
        $record = new ResultRecord(['id' => $recordId]);
        $records = [$record];
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn($records);
        $repo = $this->createMock(PaymentTransactionRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getPaymentMethods')
            ->with(Order::class, [$recordId])
            ->willReturn($returnResult);

        $this->listener->onResultAfter($event);
        $this->assertEquals($expectation, $record->getValue('paymentMethods'));
    }

    public function methodsDataProvider(): array
    {
        return [
            'one method exists' => [
                'returnResult' => [
                    1 => [
                        'payment_term',
                    ],
                ],
                'expectation' => ['payment_term'],
            ],
            'few method exists' => [
                'returnResult' => [
                    1 => [
                        'payment_term',
                    ],
                    2 => [
                        'payment_term',
                    ],
                ],
                'expectation' => ['payment_term'],
            ],
            'no one method exists' => [
                'returnResult' => [],
                'expectation' => [],
            ],
        ];
    }
}
