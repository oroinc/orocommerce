<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserTypedAddressType;

class FrontendCustomerUserTypedAddressTypeTest extends FrontendCustomerTypedAddressTypeTest
{
    /** @var FrontendCustomerUserTypedAddressType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendCustomerUserTypedAddressType();
        $this->formType->setAddressTypeDataClass('Oro\Bundle\AddressBundle\Entity\AddressType');
        $this->formType->setDataClass('Oro\Bundle\CustomerBundle\Entity\CustomerAddress');
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('oro_customer_frontend_customer_user_typed_address', $this->formType->getName());
    }

    /**
     * @return CustomerUser
     */
    protected function getCustomer()
    {
        return new CustomerUser();
    }
}
