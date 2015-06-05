<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Model;

use Oro\Bundle\ApplicationBundle\Model\AbstractModel;

class AbstractModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntities()
    {
        $entity = new \stdClass();
        $entity->id = 1;

        /** @var AbstractModel $abstractModel */
        $abstractModel = $this->getMockBuilder('Oro\Bundle\ApplicationBundle\Model\AbstractModel')
            ->setConstructorArgs([$entity])
            ->getMockForAbstractClass();

        $this->assertAttributeEquals($entity, 'entity', $abstractModel);
        $this->assertEquals([$entity], $abstractModel->getEntities());
    }
}
