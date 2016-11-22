<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\CustomerBundle\Entity\Audit;

class AuditFieldTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 2],
        ];

        static::assertPropertyAccessors(new AuditField('field1', 'string', 'value', 'oldValue'), $properties);
    }

    public function testGetters()
    {
        $audit = new Audit();
        $auditField = new AuditField('field1', 'string', 'value', 'oldValue');
        $auditField->setAudit($audit);

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
        new AuditField('field1', 'string1', 'value', 'oldValue');
    }
}
