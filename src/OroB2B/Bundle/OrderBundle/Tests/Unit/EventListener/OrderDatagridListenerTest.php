<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\OrderDatagridListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

class OrderDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderDatagridListener
     */
    protected $listener;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->listener = new OrderDatagridListener($this->doctrineHelper);
    }

    /**
     * @dataProvider methodsDataProvider
     * @param array $returnResult
     * @param array $expectation
     */
    public function testOnResultAfter($returnResult, $expectation)
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $eventMock */
        $eventMock = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $recordId = 1;
        $record = new ResultRecord(['id' => $recordId]);
        $records = [$record];
        $eventMock->expects($this->once())->method('getRecords')->willReturn($records);
        $repoMock = $this->getMockBuilder(PaymentTransactionRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->with(PaymentTransaction::class)
            ->willReturn($repoMock);
        $repoMock->expects($this->once())->method('getPaymentMethods')->with(Order::class, [$recordId])
            ->willReturn($returnResult);

        $this->listener->onResultAfter($eventMock);
        $this->assertEquals($expectation, $record->getValue('paymentMethods'));
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
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
