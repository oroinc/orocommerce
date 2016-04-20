<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginType;

class ShippingOriginTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ShippingOriginType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $buildAddressFormListener = $this->getMockBuilder(
            'Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber'
        )->disableOriginalConstructor()->getMock();

        $this->formType = new ShippingOriginType($buildAddressFormListener);
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin',
                'intention' => 'shipping_origin',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getName());
    }
}
