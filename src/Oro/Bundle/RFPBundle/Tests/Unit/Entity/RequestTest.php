<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait, EntityTrait;

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

        $properties = [
            ['id', 42],
            ['firstName', 'Grzegorz'],
            ['lastName', 'Brzeczyszczykiewicz'],
            ['email', 'john.dow@example.com'],
            ['phone', '(555)5555-555-55'],
            ['company', 'JohnDow Inc.'],
            ['role', 'cto'],
            ['note', 'test_request_notes'],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
            ['website', new Website()],
            ['shipUntil', new \DateTime('now', new \DateTimeZone('UTC'))],
        ];

        $propertyRequest = new Request();

        $this->assertPropertyAccessors($propertyRequest, $properties);

        $this->assertPropertyCollections(
            $propertyRequest,
            [
                ['requestProducts', new RequestProduct()],
                ['assignedUsers', new User()],
                ['assignedCustomerUsers', new CustomerUser()],
                ['requestAdditionalNotes', new RequestAdditionalNote()],
            ]
        );
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
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['organization', new Organization()],
            ['owner', new User()]
        ];

        $this->assertPropertyAccessors(new Request(), $properties);
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
        ReflectionUtil::setId($request, $id);
        $request->setFirstName($firstName);
        $request->setLastName($lastName);

        $this->assertEquals(sprintf('%s: %s %s', $id, $firstName, $lastName), (string)$request);
    }

    public function testGetIdentifier()
    {
        $poNumber = 'testNumber';
        $request = new Request();
        $request->setPoNumber($poNumber);

        $this->assertEquals($poNumber, $request->getIdentifier());
    }

    public function testOwnerEmailInterface()
    {
        $request = $this->getEntity(Request::class, [
            'id' => 74,
            'firstName' => 'first',
            'lastName' => 'last'
        ]);

        $this->assertEquals(74, $request->getId());
        $this->assertEquals('first', $request->getFirstName());
        $this->assertEquals('last', $request->getLastName());
        $this->assertEquals(['email'], $request->getEmailFields());
    }
}
