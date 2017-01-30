<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\CreditCardType;
use Oro\Bundle\PayPalBundle\Entity\ExpressCheckoutPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayPalSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var PayPalSettingsType */
    private $formType;

    /** @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
    private $encoder;

    public function setUp()
    {
        parent::setUp();

        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->formType = new PayPalSettingsType($translator, $this->encoder);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([
            'creditCardPaymentAction' => new CreditCardPaymentAction(),
            'expressCheckoutPaymentAction' => new ExpressCheckoutPaymentAction(),
            'mastercard' => new CreditCardType(),
            'visa' => new CreditCardType(),
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
        static::assertSame(PayPalSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testSubmit()
    {
        $submitData = [
            'creditCardLabels' => [['string' => 'creditCard']],
            'creditCardShortLabels' => [['string' => 'creditCardShort']],
            'expressCheckoutLabels' => [['string' => 'expressCheckout']],
            'expressCheckoutShortLabels' => [['string' => 'expressCheckoutShort']],
            'expressCheckoutName' => 'checkoutName',
            'allowedCreditCardTypes' => ['mastercard', 'visa'],
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

        $this->encoder
            ->expects(static::any())
            ->method('encryptData')
            ->willReturnMap([
                [$submitData['vendor'], $submitData['vendor']],
                [$submitData['partner'], $submitData['partner']],
                [$submitData['user'], $submitData['user']],
                [$submitData['password'], $submitData['password']],
                [$submitData['proxyHost'], $submitData['proxyHost']],
                [$submitData['proxyPort'], $submitData['proxyPort']],
            ]);

        $payPalSettings = new PayPalSettings();

        $form = $this->factory->create($this->formType, $payPalSettings);

        $form->submit($submitData);

        static::assertTrue($form->isValid());
        static::assertEquals($payPalSettings, $form->getData());
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
