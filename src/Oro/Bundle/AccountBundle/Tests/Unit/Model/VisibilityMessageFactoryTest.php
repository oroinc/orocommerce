<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageFactory;
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
                'className' => 'Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility',
                'id' => 42,
                'expected' => [
                    VisibilityMessageFactory::ID => 42,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                        => 'Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility'
                ]
            ],
            'accountProductVisibility' => [
                'className' => 'Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility',
                'id' => 123,
                'expected' => [
                    VisibilityMessageFactory::ID => 123,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                    => 'Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility'
                ]
            ],
            'accountGroupProductVisibility' => [
                'className' => 'Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility',
                'id' => 321,
                'expected' => [
                    VisibilityMessageFactory::ID => 321,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME
                    => 'Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility'
                ]
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should not be empty
     */
    public function testGetVisibilityFromMessageEmptyException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([]);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Visibility id is required
     */
    public function testGetVisibilityFromMessageRequiredIdException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([
            VisibilityMessageFactory::ID => null,
            VisibilityMessageFactory::ENTITY_CLASS_NAME => 'ProductVisibility'
        ]);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Visibility class name is required
     */
    public function testGetVisibilityFromMessageRequiredClassNameException()
    {
        $this->visibilityMessageFactory->getEntityFromMessage([VisibilityMessageFactory::ID => 42]);
    }
}
