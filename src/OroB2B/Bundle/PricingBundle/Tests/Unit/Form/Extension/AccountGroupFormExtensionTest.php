<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilder;

use OroB2B\Bundle\PricingBundle\Form\Extension\AccountGroupFormExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;

class AccountGroupFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return AccountGroupFormExtension
     */
    protected function getExtension()
    {
        return new AccountGroupFormExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_account_group_type', $this->getExtension()->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'priceListsByWebsites',
                AccountGroupWebsiteScopedPriceListsType::NAME
            );
        $this->getExtension()->buildForm($builder, []);
    }
}
