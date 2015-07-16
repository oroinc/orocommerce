<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Audit;

class AuditTest extends EntityTestCase
{
    public function testUser()
    {
        $user = new AccountUser();
        $audit = new Audit();
        $audit->setUser($user);
        $this->assertSame($user, $audit->getUser());
    }

    public function testAccessors()
    {
        $properties = [
            ['objectName', (string)(new AccountUser())],
            ['objectId', 2],
            ['organization', new Organization()],
            ['id', 2],
            ['action', 'some_action'],
            ['version', 1],
        ];

        static::assertPropertyAccessors(new Audit(), $properties);
    }

    public function testLoggedAt()
    {
        $audit = new Audit();
        $audit->setLoggedAt();
        $this->assertInstanceOf('\DateTime', $audit->getLoggedAt());
    }

    public function testFields()
    {
        $audit = new Audit();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $audit->getFields());

        $audit->createField('field1', 'string', 'a', 'b');
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $audit->getFields());

        $audit->getFields()->map(
            function ($field) {
                $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Entity\AuditField', $field);
            }
        );

        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Entity\AuditField', $audit->getField('field1'));
        $this->assertFalse($audit->getField('field2'));

        $audit->createField('field1', 'string', 'a2', 'b2');
        $this->assertEquals('a2', $audit->getField('field1')->getNewValue());
        $this->assertEquals('b2', $audit->getField('field1')->getOldValue());
    }

    public function testFieldsData()
    {
        $audit = new Audit();
        $audit->createField('field1', 'string', 'a2', 'b2');
        $this->assertEquals(['field1' => ['old' => 'b2', 'new' => 'a2']], $audit->getData());

        $date = new \DateTime();
        $audit->createField('date', 'datetime', $date, null);
        $this->assertEquals(
            [
                'field1' => ['old' => 'b2', 'new' => 'a2'],
                'date' => [
                    'old' => ['value' => null, 'type' => 'datetime'],
                    'new' => ['value' => $date, 'type' => 'datetime'],
                ],
            ],
            $audit->getData()
        );
    }
}
