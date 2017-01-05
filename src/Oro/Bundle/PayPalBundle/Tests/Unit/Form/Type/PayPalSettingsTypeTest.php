<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class PayPalSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var PayPalSettingsType */
    private $formType;

    public function setUp()
    {
        $this->formType = new PayPalSettingsType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->createMock(ManagerRegistry::class);

        return [
            new PreloadedExtension(
                [
                    new LocalizedPropertyType(),
                    new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($registry),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetBlockPrefixReturnsCorrectString()
    {
        static::assertSame('oro_pay_pal_settings', $this->formType->getBlockPrefix());
    }

    public function testSubmit()
    {
        $submitData = [
            'creditCardLabels' => '',
            'creditCardShortLabels' => '',
            'expressCheckoutLabels' => '',
            'expressCheckoutShortLabels' => '',
            'expressCheckoutName' => 'checkoutName',
            'creditCardPaymentAction' => 'Authorize',
            'expressCheckoutPaymentAction' => 'Authorize',
            'allowedCreditCardTypes' => ['Visa'],
            'partner' => 'partner',
            'vendor' => 'vendor',
            'user' => 'user',
            'password' => 'pass',
            'testMode' => true,
            'debugMode' => false,
            'requireCVV' => true,
            'zeroAmountAuthorization' => false,
            'requiredAuthorization' => false,
            'useProxy' => false,
        ];

        $payPalSettings = new \StdClass();

        $form = $this->factory->create($this->formType, $payPalSettings);

        $this->assertSame($payPalSettings, $form->getData());

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($payPalSettings, $form->getData());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => PayPalSettings::class
            ]);

        $this->formType->configureOptions($resolver);
    }
}
