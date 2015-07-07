<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;

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
}
