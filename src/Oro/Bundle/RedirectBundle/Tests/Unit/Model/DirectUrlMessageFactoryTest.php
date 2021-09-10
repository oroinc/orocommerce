<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DirectUrlMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var DirectUrlMessageFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = new DirectUrlMessageFactory($this->registry, $this->configManager);
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     * @param string $strategy
     * @param bool $requestCreateRedirect
     * @param bool $expectedCreateRedirect
     */
    public function testCreateMessage($strategy, $requestCreateRedirect, $expectedCreateRedirect)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);
        $entity = new SluggableEntityStub();
        $entity->setId(42);
        $entity->getSlugPrototypesWithRedirect()->setCreateRedirect($requestCreateRedirect);

        $this->assertEquals(
            [
                'class' => SluggableEntityStub::class,
                'id' => 42,
                'createRedirect' => $expectedCreateRedirect
            ],
            $this->factory->createMessage($entity)
        );
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     * @param string $strategy
     * @param bool $requestCreateRedirect
     * @param bool $expectedCreateRedirect
     */
    public function testCreateMassMessage($strategy, $requestCreateRedirect, $expectedCreateRedirect)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $this->assertEquals(
            [
                'class' => SluggableEntityStub::class,
                'id' => [1, 2, 3],
                'createRedirect' => $expectedCreateRedirect
            ],
            $this->factory->createMassMessage(SluggableEntityStub::class, [1, 2, 3], $requestCreateRedirect)
        );
    }

    /**
     * @return array
     */
    public function redirectStrategyDataProvider()
    {
        return [
            'if strategy is always then expectedCreateRedirect is always true #1' => [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'requestCreateRedirect' => false,
                'expectedCreateRedirect' => true
            ],
            'if strategy is always then expectedCreateRedirect is always true #2' => [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'requestCreateRedirect' => true,
                'expectedCreateRedirect' => true
            ],
            'if strategy is never then expectedCreateRedirect is always false #1' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'requestCreateRedirect' => false,
                'expectedCreateRedirect' => false
            ],
            'if strategy is never then expectedCreateRedirect is always false #2' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'requestCreateRedirect' => true,
                'expectedCreateRedirect' => false
            ],
            'if strategy is ask then expectedCreateRedirect equals to requestCreateRedirect #1' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'requestCreateRedirect' => true,
                'expectedCreateRedirect' => true
            ],
            'if strategy is ask then expectedCreateRedirect equals to requestCreateRedirect #2' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'requestCreateRedirect' => false,
                'expectedCreateRedirect' => false
            ]
        ];
    }

    public function testGetEntityClassFromMessage()
    {
        $message = ['class' => SluggableEntityStub::class, 'id' => 42];
        $this->assertEquals(SluggableEntityStub::class, $this->factory->getEntityClassFromMessage($message));
    }

    /**
     * @dataProvider invalidMessagesDataProvider
     */
    public function testGetEntitiesFromMessageInvalidMessage(array $message)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->getEntitiesFromMessage($message);
    }

    /**
     * @return array
     */
    public function invalidMessagesDataProvider()
    {
        return [
            'empty array' => [[]],
            'no class' => [['id' => 1]],
            'no id' => [['class' => SluggableEntityStub::class]],
            'unsupported class' => [['id' => 1, 'class' => \DateTime::class]],
            'non integer id' => [['id' => 'one', 'class' => SluggableEntityStub::class]],
            'non string class' => [['id' => 'one', 'class' => 123]],
            'not bool require cache calculation' => [['id' => 1, 'class' => '123', 'requireCacheCalculation' => '3']],
        ];
    }

    public function testGetEntitiesFromMessage()
    {
        $message = [
            'id' => 1,
            'class' => SluggableEntityStub::class
        ];
        $entity = new SluggableEntityStub();
        $entity->setId(1);

        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => 1])
            ->willReturn([$entity]);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(SluggableEntityStub::class)
            ->willReturn($repository);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(SluggableEntityStub::class)
            ->willReturn($metadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(SluggableEntityStub::class)
            ->willReturn($em);

        $this->assertEquals([$entity], $this->factory->getEntitiesFromMessage($message));
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     * @param string $strategy
     * @param bool $requestCreateRedirect
     * @param bool $expectedCreateRedirect
     */
    public function testGetCreateRedirectFromMessage($strategy, $requestCreateRedirect, $expectedCreateRedirect)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $data = [
            DirectUrlMessageFactory::ID => 1,
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => SluggableEntityStub::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => $requestCreateRedirect,
        ];

        $this->assertEquals($expectedCreateRedirect, $this->factory->getCreateRedirectFromMessage($data));
    }
}
