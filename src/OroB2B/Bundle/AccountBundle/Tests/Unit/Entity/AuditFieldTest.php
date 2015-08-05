<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Audit;
use OroB2B\Bundle\AccountBundle\Entity\AuditField;

class AuditFieldTest extends EntityTestCase
{
    public function testAccessors()
    {
        $audit = new Audit();

        $properties = [
            ['id', 2],
        ];

        static::assertPropertyAccessors(new AuditField($audit, 'field1', 'string', 'value', 'oldValue'), $properties);
    }

    public function testGetters()
    {
        $audit = new Audit();
        $auditField = new AuditField($audit, 'field1', 'string', 'value', 'oldValue');
        $this->assertSame($audit, $auditField->getAudit());
        $this->assertSame('field1', $auditField->getField());
        $this->assertSame('text', $auditField->getDataType());
        $this->assertSame('value', $auditField->getNewValue());
        $this->assertSame('oldValue', $auditField->getOldValue());
    }

    /**
     * @expectedException \Oro\Bundle\DataAuditBundle\Exception\UnsupportedDataTypeException
     * @expectedExceptionMessage Unsupported audit data type "string1"
     */
    public function testUnsupportedType()
    {
        new AuditField(new Audit(), 'field1', 'string1', 'value', 'oldValue');
    }
}
