<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestStatusTestCase extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['name', 'opened'],
            ['label', 'Opened'],
            ['sortOrder', 1],
            ['deleted', true],
            ['deleted', false],
        ];

        $propertyRequestStatus = new RequestStatus();

        $this->assertPropertyAccessors($propertyRequestStatus, $properties);
    }

    /**
     * Test toString
     */
    public function testToString()
    {
        $value = 'Opened';

        $requestStatus = new RequestStatus();
        $requestStatus->setLabel($value);

        $this->assertEquals($value, (string)$requestStatus);
    }
}
