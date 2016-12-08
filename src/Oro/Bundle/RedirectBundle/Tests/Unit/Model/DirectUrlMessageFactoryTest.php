<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var DirectUrlMessageFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->factory = new DirectUrlMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $entity = new SluggableEntityStub();
        $entity->setId(42);

        $this->assertEquals(
            ['class' => SluggableEntityStub::class, 'id' => 42],
            $this->factory->createMessage($entity)
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
    public function testGetEntityFromMessageInvalidMessage(array $message)
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $this->factory->getEntityFromMessage($message);
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

    public function testGetEntityFromMessage()
    {
        $message = [
            'id' => 1,
            'class' => SluggableEntityStub::class
        ];
        $entity = new SluggableEntityStub();
        $entity->setId(1);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(SluggableEntityStub::class, 1)
            ->willReturn($entity);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(SluggableEntityStub::class)
            ->willReturn($em);

        $this->assertEquals($entity, $this->factory->getEntityFromMessage($message));
    }
}
