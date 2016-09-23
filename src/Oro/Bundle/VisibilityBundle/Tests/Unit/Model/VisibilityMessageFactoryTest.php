<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class VisibilityMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var VisibilityMessageFactory
     */
    protected $visibilityMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->visibilityMessageFactory = new VisibilityMessageFactory($this->registry);
    }

    /**
     * @dataProvider createMessageDataProvider
     *
     * @param string $className
     * @param int $id
     * @param array $expected
     */
    public function testCreateMessage($className, $id, array $expected)
    {
        /** @var VisibilityInterface $visibility **/
        $visibility = $this->getEntity($className, ['id' => $id]);

        $actual = $this->visibilityMessageFactory->createMessage($visibility);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function createMessageDataProvider()
    {
        return [
            'productVisibility' => [
                'className' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility',
                'id' => 42,
                'expected' => [
                    VisibilityMessageFactory::ID => 42,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                        => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility'
                ]
            ],
            'accountProductVisibility' => [
                'className' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility',
                'id' => 123,
                'expected' => [
                    VisibilityMessageFactory::ID => 123,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                    => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility'
                ]
            ],
            'accountGroupProductVisibility' => [
                'className' => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility',
                'id' => 321,
                'expected' => [
                    VisibilityMessageFactory::ID => 321,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                    => 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility'
                ]
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should not be empty
     */
    public function testGetEntityFromMessageEmptyException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Visibility id is required
     */
    public function testGetEntityFromMessageRequiredIdException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([
            VisibilityMessageFactory::ID => null,
            VisibilityMessageFactory::ENTITY_CLASS_NAME => 'ProductVisibility'
        ]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Visibility class name is required
     */
    public function testGetEntityFromMessageRequiredClassNameException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([VisibilityMessageFactory::ID => 42]);
    }
}
