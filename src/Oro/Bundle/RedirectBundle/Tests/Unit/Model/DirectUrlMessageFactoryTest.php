<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DirectUrlMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private DirectUrlMessageFactory $factory;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->factory = new DirectUrlMessageFactory($this->registry);
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     */
    public function testCreateMessage(bool $requestCreateRedirect): void
    {
        $entity = new SluggableEntityStub();
        $entity->setId(42);
        $entity->getSlugPrototypesWithRedirect()->setCreateRedirect($requestCreateRedirect);

        self::assertEquals(
            [
                'class' => SluggableEntityStub::class,
                'id' => 42,
                'createRedirect' => $requestCreateRedirect,
            ],
            $this->factory->createMessage($entity)
        );
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     * @param bool $requestCreateRedirect
     */
    public function testCreateMassMessage($requestCreateRedirect): void
    {
        self::assertEquals(
            [
                'class' => SluggableEntityStub::class,
                'id' => [1, 2, 3],
                'createRedirect' => $requestCreateRedirect,
            ],
            $this->factory->createMassMessage(SluggableEntityStub::class, [1, 2, 3], $requestCreateRedirect)
        );
    }

    /**
     * @return array
     */
    public function redirectStrategyDataProvider(): array
    {
        return [
            'expectedCreateRedirect equals to requestCreateRedirect #1' => [
                'requestCreateRedirect' => true,
            ],
            'expectedCreateRedirect equals to requestCreateRedirect #2' => [
                'requestCreateRedirect' => false,
            ],
        ];
    }

    public function testGetEntityClassFromMessage(): void
    {
        $message = ['class' => SluggableEntityStub::class, 'id' => 42];
        self::assertEquals(SluggableEntityStub::class, $this->factory->getEntityClassFromMessage($message));
    }

    public function testGetEntitiesFromMessage(): void
    {
        $message = [
            'id' => 1,
            'class' => SluggableEntityStub::class,
        ];
        $entity = new SluggableEntityStub();
        $entity->setId(1);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => 1])
            ->willReturn([$entity]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(SluggableEntityStub::class)
            ->willReturn($repository);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(SluggableEntityStub::class)
            ->willReturn($metadata);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(SluggableEntityStub::class)
            ->willReturn($em);

        self::assertEquals([$entity], $this->factory->getEntitiesFromMessage($message));
    }

    /**
     * @dataProvider redirectStrategyDataProvider
     */
    public function testGetCreateRedirectFromMessage(bool $requestCreateRedirect): void
    {
        $data = [
            DirectUrlMessageFactory::ID => 1,
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => SluggableEntityStub::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => $requestCreateRedirect,
        ];

        self::assertEquals($requestCreateRedirect, $this->factory->getCreateRedirectFromMessage($data));
    }
}
