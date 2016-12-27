<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Symfony\Component\Form\FormEvent;

class DestinationCollectionTypeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for DestinationCollectionTypeSubscriber.preSubmit
     */
    public function testPreSubmitMethod()
    {
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
            ->with(['destinations' => [
                ['country' => 0]
            ]]);

        $subscriber = new DestinationCollectionTypeSubscriber();
        $subscriber->preSubmit($event);
    }

    /**
     * @dataProvider noChangesDataProvider
     *
     * @param array $data
     */
    public function testNotChangesInPreSubmitMethod($data)
    {
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
