<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\EventListener\ConfigRegenerateDirectUrlListener;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigRegenerateDirectUrlListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var RedirectStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    private $redirectStorage;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
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

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->redirectStorage = $this->createMock(RedirectStorage::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->configParameter = 'some_parameter';
        $this->entityClass = \stdClass::class;

        $this->listener = new ConfigRegenerateDirectUrlListener(
            $this->configManager,
            $this->messageProducer,
            $this->redirectStorage,
            $this->messageFactory,
            $this->configParameter,
            $this->entityClass
        );
    }

    public function testOnUpdateNotChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
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

    public function testOnUpdatePrefixChange()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
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

        $this->configManager->expects($this->never())
            ->method($this->anything());

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

    /**
     * @dataProvider onUpdateUseDefaultDataProvider
     *
     * @param string $strategy
     * @param bool $createRedirect
     */
    public function testOnUpdateUseDefault($strategy, $createRedirect)
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
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
            ->willReturn(null);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

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

    /**
     * @return array
     */
    public function onUpdateUseDefaultDataProvider()
    {
        return [
            'Ask strategy' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'createRedirect' => true
            ],
            'Always strategy' => [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'createRedirect' => true
            ],
            'Never strategy' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'createRedirect' => false
            ]
        ];
    }
}
