<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\ExpressCheckoutPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayPalSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var PayPalSettingsType */
    private $formType;

    public function setUp()
    {
        parent::setUp();

        $this->formType = new PayPalSettingsType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([
            'creditCardPaymentAction' => new CreditCardPaymentAction(),
            'expressCheckoutPaymentAction' => new ExpressCheckoutPaymentAction(),
        ]);
        $localizedType = new LocalizedFallbackValueCollectionTypeStub();

        return [
            new PreloadedExtension(
                [
                    $localizedType->getName() => $localizedType,
                    $entityType->getName() => $entityType,
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
            'creditCardLabels' => [['string' => 'creditCard']],
            'creditCardShortLabels' => [['string' => 'creditCardShort']],
            'expressCheckoutLabels' => [['string' => 'expressCheckout']],
            'expressCheckoutShortLabels' => [['string' => 'expressCheckoutShort']],
            'expressCheckoutName' => 'checkoutName',
            'creditCardPaymentAction' => 'creditCardPaymentAction',
            'expressCheckoutPaymentAction' => 'expressCheckoutPaymentAction',
            'partner' => 'partner',
            'vendor' => 'vendor',
            'user' => 'user',
            'password' => 'pass',
            'testMode' => true,
            'debugMode' => false,
            'requireCVVEntry' => true,
            'zeroAmountAuthorization' => false,
            'authorizationForRequiredAmount' => false,
            'useProxy' => false,
            'proxyHost' => 'host',
            'proxyPort' => 'port',
            'enableSSLVerification' => false,
        ];

        $payPalSettings = new PayPalSettings();

        $form = $this->factory->create($this->formType, $payPalSettings);

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
