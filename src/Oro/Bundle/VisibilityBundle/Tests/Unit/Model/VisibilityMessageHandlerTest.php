<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class VisibilityMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var VisibilityMessageHandler
     */
    protected $visibilityMessageHandler;

    protected function setUp()
    {
        $this->messageFactory = $this->getMockBuilder(VisibilityMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->visibilityMessageHandler = new VisibilityMessageHandler(
            $this->messageFactory,
            $this->messageProducer
        );
    }

    public function testAddMessagesForProductVisibility()
    {
        /** @var ProductVisibility $productVisibility **/
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => 42]);

        /** @var CustomerProductVisibility $customerProductVisibility **/
        $customerProductVisibility = $this->getEntity(CustomerProductVisibility::class, ['id' => 123]);

        /** @var CustomerGroupProductVisibility $customerGroupProductVisibility **/
        $customerGroupProductVisibility = $this->getEntity(CustomerGroupProductVisibility::class, ['id' => 321]);

        $this->messageFactory->expects($this->any())
            ->method('createMessage')
            ->willReturnMap([
                [
                    $productVisibility,
                    [
                        VisibilityMessageFactory::ID => 42,
                        VisibilityMessageFactory::ENTITY_CLASS_NAME => ClassUtils::getClass($productVisibility)
                    ]
                ],
                [
                    $customerProductVisibility,
                    [
                        VisibilityMessageFactory::ID => 123,
                        VisibilityMessageFactory::ENTITY_CLASS_NAME
                            => ClassUtils::getClass($customerProductVisibility)
                    ]
                ],
                [
                    $customerGroupProductVisibility,
                    [
                        VisibilityMessageFactory::ID => 321,
                        VisibilityMessageFactory::ENTITY_CLASS_NAME
                            => ClassUtils::getClass($customerGroupProductVisibility)
                    ]
                ]
            ]);

        // Add same message twice
        $this->visibilityMessageHandler->addMessageToSchedule(
            'oro_visibility.visibility.resolve_product_visibility',
            $productVisibility
        );
        $this->visibilityMessageHandler->addMessageToSchedule(
            'oro_visibility.visibility.resolve_product_visibility',
            $productVisibility
        );

        // Add another messages
        $this->visibilityMessageHandler->addMessageToSchedule(
            'oro_visibility.visibility.resolve_product_visibility',
            $customerProductVisibility
        );
        $this->visibilityMessageHandler->addMessageToSchedule(
            'oro_visibility.visibility.resolve_product_visibility',
            $customerGroupProductVisibility
        );

        $this->assertAttributeEquals(
            ['oro_visibility.visibility.resolve_product_visibility' => [
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility:42' => [
                    VisibilityMessageFactory::ID => 42,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME =>
                        'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility'
                ],
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility:123' => [
                    VisibilityMessageFactory::ID => 123,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME =>
                        'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility'
                ],
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility:321' => [
                    VisibilityMessageFactory::ID => 321,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME =>
                        'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility'
                ]
            ]],
            'scheduledMessages',
            $this->visibilityMessageHandler
        );
    }

    public function testSendScheduledMessages()
    {
        /** @var ProductVisibility $productVisibility **/
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => 42]);

        $message = [
            VisibilityMessageFactory::ID => 42,
            VisibilityMessageFactory::ENTITY_CLASS_NAME => ClassUtils::getClass($productVisibility)
        ];

        $this->messageFactory->expects($this->any())
            ->method('createMessage')
            ->with($productVisibility)
            ->willReturn($message);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with('oro_visibility.visibility.resolve_product_visibility', $message);

        $this->visibilityMessageHandler->addMessageToSchedule(
            'oro_visibility.visibility.resolve_product_visibility',
            $productVisibility
        );

        $this->visibilityMessageHandler->sendScheduledMessages();
    }
}
