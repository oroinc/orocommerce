<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AccountBundle\Async\Topics;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class VisibilityMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var VisibilityMessageHandler
     */
    protected $visibilityTriggerHandler;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(VisibilityMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);
        $this->visibilityTriggerHandler = new VisibilityMessageHandler(
            $this->triggerFactory,
            $this->messageProducer
        );
    }

    public function testAddTriggersForProductVisibility()
    {
        /** @var ProductVisibility $productVisibility **/
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => 42]);

        /** @var AccountProductVisibility $accountProductVisibility **/
        $accountProductVisibility = $this->getEntity(AccountProductVisibility::class, ['id' => 123]);

        /** @var AccountGroupProductVisibility $accountGroupProductVisibility **/
        $accountGroupProductVisibility = $this->getEntity(AccountGroupProductVisibility::class, ['id' => 321]);

        $this->triggerFactory->expects($this->any())
            ->method('createMessage')
            ->willReturnMap([
                [
                    $productVisibility,
                    [
                        VisibilityMessageFactory::ID => 42,
                        VisibilityMessageFactory::VISIBILITY_CLASS => ClassUtils::getClass($productVisibility)
                    ]
                ],
                [
                    $accountProductVisibility,
                    [
                        VisibilityMessageFactory::ID => 123,
                        VisibilityMessageFactory::VISIBILITY_CLASS
                            => ClassUtils::getClass($accountProductVisibility)
                    ]
                ],
                [
                    $accountGroupProductVisibility,
                    [
                        VisibilityMessageFactory::ID => 321,
                        VisibilityMessageFactory::VISIBILITY_CLASS
                            => ClassUtils::getClass($accountGroupProductVisibility)
                    ]
                ]
            ]);

        // Add same trigger twice
        $this->visibilityTriggerHandler->addVisibilityMessageToSchedule(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );
        $this->visibilityTriggerHandler->addVisibilityMessageToSchedule(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );

        // Add another triggers
        $this->visibilityTriggerHandler->addVisibilityMessageToSchedule(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $accountProductVisibility
        );
        $this->visibilityTriggerHandler->addVisibilityMessageToSchedule(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $accountGroupProductVisibility
        );

        $this->assertAttributeEquals(
            ['orob2b_account.visibility.resolve_product_visibility' => [
                'Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility:42' => [
                    VisibilityMessageFactory::ID => 42,
                    VisibilityMessageFactory::VISIBILITY_CLASS =>
                        'Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility'
                ],
                'Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility:123' => [
                    VisibilityMessageFactory::ID => 123,
                    VisibilityMessageFactory::VISIBILITY_CLASS =>
                        'Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility'
                ],
                'Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility:321' => [
                    VisibilityMessageFactory::ID => 321,
                    VisibilityMessageFactory::VISIBILITY_CLASS =>
                        'Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility'
                ]
            ]],
            'scheduledMessages',
            $this->visibilityTriggerHandler
        );
    }

    public function testSendScheduledTriggers()
    {
        /** @var ProductVisibility $productVisibility **/
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => 42]);

        $trigger = [
            VisibilityMessageFactory::ID => 42,
            VisibilityMessageFactory::VISIBILITY_CLASS => ClassUtils::getClass($productVisibility)
        ];

        $this->triggerFactory->expects($this->any())
            ->method('createMessage')
            ->with($productVisibility)
            ->willReturn($trigger);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_PRODUCT_VISIBILITY, $trigger);

        $this->visibilityTriggerHandler->addVisibilityMessageToSchedule(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );

        $this->visibilityTriggerHandler->sendScheduledMessages();
    }
}
