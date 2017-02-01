<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;

class DirectUrlMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var DirectUrlMessageFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = new DirectUrlMessageFactory($this->registry, $this->configManager);
    }

    public function testCreateMessage()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn('always');
        $entity = new SluggableEntityStub();
        $entity->setId(42);

        $this->assertEquals(
            ['class' => SluggableEntityStub::class, 'id' => 42, 'createRedirect' => true],
            $this->factory->createMessage($entity)
        );
    }

    public function testCreateMassMessage()
    {
        $this->assertEquals(
            ['class' => SluggableEntityStub::class, 'id' => [1, 2, 3]],
            $this->factory->createMassMessage(SluggableEntityStub::class, [1, 2, 3])
        );
    }

    public function testGetEntityClassFromMessage()
    {
        $message = ['class' => SluggableEntityStub::class, 'id' => 42];
        $this->assertEquals(SluggableEntityStub::class, $this->factory->getEntityClassFromMessage($message));
    }

    /**
     * @dataProvider invalidMessagesDataProvider
     * @param array $message
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

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => 1])
            ->willReturn([$entity]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
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

    public function testGetCreateRedirectFromMessage()
    {
        $data = [
            DirectUrlMessageFactory::ID => 1,
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => 'ClassName',
            DirectUrlMessageFactory::CREATE_REDIRECT => true,
        ];

        $createRedirect = $this->factory->getCreateRedirectFromMessage($data);
        $this->assertTrue($createRedirect);
    }
}
