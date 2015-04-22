<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestTestCase extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $date = new \DateTime();

        $propertyStatus = new RequestStatus();

        $properties = [
            ['id', 42],
            ['firstName', 'Grzegorz'],
            ['lastName', 'Brzeczyszczykiewicz'],
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

    /**
     * Test construct
     */
    public function testConstruct()
    {
        $request = new Request();

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $request->getCreatedAt());
        $this->assertLessThanOrEqual($now, $request->getCreatedAt());

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $request->getUpdatedAt());
    }

    /**
     * Test preUpdate
     */
    public function testPreUpdate()
    {
        $request = new Request();
        $request->preUpdate();

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $request->getUpdatedAt());
    }

    /**
     * Test toString
     */
    public function testToString()
    {
        $id = 42;
        $firstName = 'Grzegorz';
        $lastName  = 'Brzeczyszczykiewicz';

        $request = new Request();
        $request->setFirstName($firstName)
            ->setLastName($lastName);

        $reflectionProperty = new \ReflectionProperty(get_class($request), 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($request, $id);

        $this->assertEquals(sprintf('%s: %s %s', $id, $firstName, $lastName), (string)$request);
    }
}
