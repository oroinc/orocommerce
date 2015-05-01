<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;

class GroupTest extends EntityTestCase
{
    const GROUP_NAME = 'test';

    /**
     * Test toString
     */
    public function testToString()
    {
        $value = 'Test group';

        $group = new Group(self::GROUP_NAME);
        $group->setName($value);

        $this->assertEquals($value, (string)$group);
    }
}
