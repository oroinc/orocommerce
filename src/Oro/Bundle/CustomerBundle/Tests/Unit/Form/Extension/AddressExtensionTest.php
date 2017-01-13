<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Form\Extension\AddressExtension;

class AddressExtensionTest extends AbstractCustomerUserAwareExtensionTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new AddressExtension($this->tokenStorage);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_address', $this->extension->getExtendedType());
    }

    public function testConfigureOptionsNonCustomerUser()
    {
        $this->assertOptionsNotChangedForNonCustomerUser();
    }

    public function testConfigureOptionsCustomerUser()
    {
        $this->assertCustomerUserTokenCall();

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('region_route', 'oro_api_frontend_country_get_regions');

        $this->extension->configureOptions($resolver);
    }
}
