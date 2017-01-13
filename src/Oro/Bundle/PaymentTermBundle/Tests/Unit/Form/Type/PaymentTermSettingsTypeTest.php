<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class PaymentTermSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var PaymentTermSettingsType */
    private $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new PaymentTermSettingsType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmitValid()
    {
        $submitData = [
            'labels' => [['string' => 'first label']],
        ];

        $settings = new PaymentTermSettings();

        $form = $this->factory->create($this->formType, $settings);
        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($settings, $form->getData());
    }

    public function testGetBlockPrefixReturnsCorrectString()
    {
        static::assertSame('oro_payment_term_settings', $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => PaymentTermSettings::class
            ]);

        $this->formType->configureOptions($resolver);
    }
}
