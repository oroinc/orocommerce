<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topic\RegenerateDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\EventListener\ConfigEnableDirectUrlListener;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigEnableDirectUrlListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var RoutingInformationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var ConfigEnableDirectUrlListener */
    private $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->provider = $this->createMock(RoutingInformationProvider::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);

        $this->listener = new ConfigEnableDirectUrlListener(
            $this->messageProducer,
            $this->provider,
            $this->messageFactory
        );
    }

    public function testOnUpdateAfterIsNotChanged(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->provider->expects(self::never())
            ->method(self::anything());
        $this->messageProducer->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterTurnedOff(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);
        $event->expects(self::once())
            ->method('getNewValue')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->provider->expects(self::once())
            ->method('getEntityClasses')
            ->willReturn(['stdClass']);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(RemoveDirectUrlForEntityTypeTopic::getName(), \stdClass::class);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterTurnedOn(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);
        $event->expects(self::once())
            ->method('getNewValue')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getEntityClasses')
            ->willReturn(['stdClass']);

        $entityClass = 'stdClass';
        $expectedMessage = [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => $entityClass,
            DirectUrlMessageFactory::CREATE_REDIRECT => false
        ];

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with($entityClass, [], false)
            ->willReturn($expectedMessage);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(RegenerateDirectUrlForEntityTypeTopic::getName(), $expectedMessage);

        $this->listener->onUpdateAfter($event);
    }
}
