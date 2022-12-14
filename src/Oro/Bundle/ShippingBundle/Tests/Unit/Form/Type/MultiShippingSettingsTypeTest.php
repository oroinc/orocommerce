<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Form\Type\MultiShippingSettingsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiShippingSettingsTypeTest extends FormIntegrationTestCase
{
    public function testConfigureOptions()
    {
        $formType = new MultiShippingSettingsType();

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => MultiShippingSettings::class
            ])
            ->willReturnSelf();

        $formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $formType = new MultiShippingSettingsType();
        $this->assertEquals('oro_multi_shipping_settings', $formType->getBlockPrefix());
    }
}
