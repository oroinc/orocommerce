<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UPSBundle\Form\Type\UPSShippingServiceCollectionType;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingServiceType;

class UPSShippingServiceCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UPSShippingServiceCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new UPSShippingServiceCollectionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'type' => UPSShippingServiceType::NAME,
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
        static::assertEquals(UPSShippingServiceCollectionType::NAME, $this->formType->getName());
    }
}
