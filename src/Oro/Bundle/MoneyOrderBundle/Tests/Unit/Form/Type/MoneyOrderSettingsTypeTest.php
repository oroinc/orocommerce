<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class MoneyOrderSettingsTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmitValid()
    {
        $submitData = [
            'payTo' => 'payTo',
            'sendTo' => 'sendTo',
            'labels' => [['string' => 'first label']],
            'shortLabels' => [['string' => 'short label']],
        ];

        $settings = new MoneyOrderSettings();

        $form = $this->factory->create(MoneyOrderSettingsType::class, $settings);
        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($settings, $form->getData());
    }

    public function testGetBlockPrefixReturnsCorrectString()
    {
        $formType = new MoneyOrderSettingsType();
        self::assertSame('oro_money_order_settings', $formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => MoneyOrderSettings::class]);
        $formType = new MoneyOrderSettingsType();
        $formType->configureOptions($resolver);
    }
}
