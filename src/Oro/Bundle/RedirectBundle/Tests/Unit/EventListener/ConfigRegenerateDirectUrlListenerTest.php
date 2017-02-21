<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\EventListener\ConfigRegenerateDirectUrlListener;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigRegenerateDirectUrlListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var RedirectStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectStorage;

    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageFactory;

    /**
     * @var string
     */
    private $configParameter;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var ConfigRegenerateDirectUrlListener
     */
    private $listener;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->redirectStorage = $this->createMock(RedirectStorage::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->configParameter = 'some_parameter';
        $this->entityClass = \stdClass::class;

        $this->listener = new ConfigRegenerateDirectUrlListener(
            $this->messageProducer,
            $this->redirectStorage,
            $this->messageFactory,
            $this->configParameter,
            $this->entityClass
        );
    }

    public function testOnUpdateAfterIsNotChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with($this->configParameter)
            ->willReturn(false);

        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->redirectStorage->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterPrefixChange()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with($this->configParameter)
            ->willReturn(true);

        $prefixWithRedirect = new PrefixWithRedirect();
        $prefixWithRedirect->setPrefix('prefix');
        $prefixWithRedirect->setCreateRedirect(true);

        $this->redirectStorage->expects($this->once())
            ->method('getPrefixByKey')
            ->with($this->configParameter)
            ->willReturn($prefixWithRedirect);

        $createRedirect = true;
        $entityClass = 'stdClass';
        $expectedMessage = [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => $entityClass,
            DirectUrlMessageFactory::CREATE_REDIRECT => $createRedirect
        ];

        $this->messageFactory->expects($this->once())
            ->method('createMassMessage')
            ->with('stdClass', [], $createRedirect)
            ->willReturn($expectedMessage);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, $expectedMessage);

        $this->listener->onUpdateAfter($event);
    }
}
