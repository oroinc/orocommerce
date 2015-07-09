<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestTest extends EntityTestCase
{
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

    public function testPreUpdate()
    {
        $request = new Request();
        $request->preUpdate();

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $request->getUpdatedAt());
    }

    /**
     * Test setters getters
     */
    public function testOwnershipAccessors()
    {
        $properties = [
            ['frontendOwner', null],
            ['frontendOwner', new AccountUser()],
            ['organization', new Organization()],
        ];

        $this->assertPropertyAccessors(new Request(), $properties);
    }

    /**
     * @depends testAccessors
     */
    public function testAddRequestProduct()
    {
        $request        = new Request();
        $requestProduct = new RequestProduct();

        $this->assertNull($requestProduct->getRequest());

        $request->addRequestProduct($requestProduct);

        $this->assertEquals($request, $requestProduct->getRequest());
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
