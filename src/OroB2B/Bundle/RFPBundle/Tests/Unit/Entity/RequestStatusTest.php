<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestStatusTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['locale', 'en'],
            ['name', 'opened'],
            ['label', 'Opened'],
            ['sortOrder', 1],
            ['deleted', true],
            ['deleted', false],
        ];

        $propertyRequestStatus = new RequestStatus();

        $this->assertPropertyAccessors($propertyRequestStatus, $properties);
    }

    public function testToString()
    {
        $value = 'Opened';

        $requestStatus = new RequestStatus();
        $requestStatus->setLabel($value);

        $this->assertEquals($value, (string)$requestStatus);
    }
}
