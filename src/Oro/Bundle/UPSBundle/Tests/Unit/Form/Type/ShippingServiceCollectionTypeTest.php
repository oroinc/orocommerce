<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UPSBundle\Form\Type\ShippingServiceCollectionType;
use Oro\Bundle\UPSBundle\Form\Type\ShippingServiceType;

class ShippingServiceCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingServiceCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new ShippingServiceCollectionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'type' => ShippingServiceType::NAME,
                'show_form_when_empty' => false
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        static::assertEquals('oro_collection', $this->formType->getParent());
    }

    public function testGetName()
    {
        static::assertEquals(ShippingServiceCollectionType::NAME, $this->formType->getName());
    }
}
