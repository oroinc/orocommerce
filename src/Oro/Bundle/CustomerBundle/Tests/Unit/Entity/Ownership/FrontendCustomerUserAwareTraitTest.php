<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Ownership\FrontendCustomerUserAwareTrait;

class FrontendCustomerUserAwareTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrontendCustomerUserAwareTrait | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendCustomerUserAwareTrait;

    protected function setUp()
    {
        $this->frontendCustomerUserAwareTrait = $this->getMockForTrait(FrontendCustomerUserAwareTrait::class);
    }

    public function testSetCustomerUser()
    {
        /** @var CustomerUser|\PHPUnit_Framework_MockObject_MockObject $customerUser **/
        $customerUser = $this->createMock(CustomerUser::class);
        $this->frontendCustomerUserAwareTrait->setCustomerUser($customerUser);

        $this->assertSame($customerUser, $this->frontendCustomerUserAwareTrait->getCustomerUser());
    }

    public function testSetCustomer()
    {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $customer **/
        $customer = $this->createMock(Account::class);
        $this->frontendCustomerUserAwareTrait->setCustomer($customer);

        $this->assertSame($customer, $this->frontendCustomerUserAwareTrait->getCustomer());
    }
}
