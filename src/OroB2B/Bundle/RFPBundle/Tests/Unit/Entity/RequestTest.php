<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestTest extends EntityTestCase
{
    public function testAccessors()
    {
        $date = new \DateTime();

        $propertyStatus = new RequestStatus();

        $properties = [
            ['id', 1],
            ['firstName', 'John'],
            ['lastName', 'Dow'],
            ['email', 'john.dow@example.com'],
            ['phone', '(555)5555-555-55'],
            ['company', 'JohnDow Inc.'],
            ['role', 'cto'],
            ['body', 'test_request_body'],
            ['status', $propertyStatus, false],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $propertyRequest = new Request();

        $this->assertPropertyAccessors($propertyRequest, $properties);
    }

    public function testConstruct()
    {
        $request = new Request();

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $request->getCreatedAt());
        $this->assertLessThanOrEqual($now, $request->getCreatedAt());

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $request->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $request = new Request();
        $request->preUpdate();

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $request->getUpdatedAt());
    }

    public function testToString()
    {
        $value = 'john.dow@example.com';

        $request = new Request();
        $request->setEmail($value);

        $this->assertEquals($value, (string)$request);
    }
}
