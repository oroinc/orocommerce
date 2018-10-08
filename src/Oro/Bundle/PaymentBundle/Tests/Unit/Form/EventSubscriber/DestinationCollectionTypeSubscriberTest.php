<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Symfony\Component\Form\FormEvent;

class DestinationCollectionTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for DestinationCollectionTypeSubscriber.preSubmit
     */
    public function testPreSubmitMethod()
    {
        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getData')
            ->willReturn([
                'destinations' => [
                    [
                        'country' => null
                    ]
                ]
            ]);

        $event
            ->expects($this->once())
            ->method('setData')
            ->with();

        $subscriber = new DestinationCollectionTypeSubscriber();
        $subscriber->preSubmit($event);
    }

    /**
     * @dataProvider noChangesDataProvider
     *
     * @param array $data
     */
    public function testNoChangesInPreSubmitMethod($data)
    {
        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $event
            ->expects($this->once())
            ->method('setData')
            ->with($data);

        $subscriber = new DestinationCollectionTypeSubscriber();
        $subscriber->preSubmit($event);
    }

    /**
     * @return array
     */
    public function noChangesDataProvider()
    {
        return [
            [
                [
                    'anotherField' => 1,
                    'destinations'  => [['country' => true]]
                ]
            ],
            [
                [
                    'destinations' => []
                ]
            ],
            [
                [
                    'destinations' => [['state' => true]]
                ]
            ],
        ];
    }
}
