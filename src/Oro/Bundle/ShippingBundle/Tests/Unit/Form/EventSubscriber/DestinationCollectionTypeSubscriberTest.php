<?php
/**
 * Date: 12/26/16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Symfony\Component\Form\FormEvent;

/**
 * Class DestinationCollectionTypeSubscriberTest
 *
 * @package Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber
 */
class DestinationCollectionTypeSubscriberTest extends \PHPUnit_Framework_TestCase
{

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
            ->with(['destinations' => []]);

        $subscriber = new DestinationCollectionTypeSubscriber();
        $subscriber->preSubmit($event);
    }

    /**
     * @dataProvider noChangesDataProvider
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
