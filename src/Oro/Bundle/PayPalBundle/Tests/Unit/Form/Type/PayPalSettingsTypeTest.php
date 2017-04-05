<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

class PayPalSettingsTypeTest extends FormIntegrationTestCase
{
    const CARD_TYPES = [
        'visa',
        'mastercard',
    ];

    const PAYMENT_ACTION = 'authorize';

    /**
     * @var PayPalSettingsType
     */
    private $formType;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $encoder;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var PaymentActionsDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentActionsDataProvider;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);

        /** @var CreditCardTypesDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $cardTypesDataProvider */
        $cardTypesDataProvider = $this->createMock(CreditCardTypesDataProviderInterface::class);
        $cardTypesDataProvider->expects($this->any())
            ->method('getCardTypes')
            ->willReturn(self::CARD_TYPES);

        $cardTypesDataProvider->expects($this->any())
            ->method('getDefaultCardTypes')
            ->willReturn(self::CARD_TYPES);

        $this->paymentActionsDataProvider = $this->createMock(PaymentActionsDataProviderInterface::class);
        $this->paymentActionsDataProvider->expects($this->any())
            ->method('getPaymentActions')
            ->willReturn([
                self::PAYMENT_ACTION,
                'charge',
            ]);

        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->formType = new PayPalSettingsType(
            $this->translator,
            $this->encoder,
            $cardTypesDataProvider,
            $this->paymentActionsDataProvider
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $localizedType = new LocalizedFallbackValueCollectionTypeStub();

        return [
            new PreloadedExtension(
                [
                    $localizedType->getName() => $localizedType,
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
            'allowedCreditCardTypes' => self::CARD_TYPES,
            'creditCardPaymentAction' => self::PAYMENT_ACTION,
            'expressCheckoutPaymentAction' => self::PAYMENT_ACTION,
            'partner' => 'partner',
            'vendor' => 'vendor',
            'user' => 'user',
            'password' => 'pass',
            'testMode' => true,
            'debugMode' => false,
            'requireCVVEntry' => false,
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

    /**
     * @dataProvider defaultValuesAreSetDataProvider
     *
     * @param string $property
     * @param mixed $value
     */
    public function testDefaultValuesAreSet($property, $value)
    {
        $payPalSettings = new PayPalSettings();
        $form = $this->factory->create($this->formType, $payPalSettings);

        static::assertEquals($value, $form->get($property)->getData());
        static::assertEquals($payPalSettings, $form->getData());
    }

    /**
     * @return array
     */
    public function defaultValuesAreSetDataProvider()
    {
        return [
            ['allowedCreditCardTypes', self::CARD_TYPES],
            ['requireCVVEntry', true],
            ['enableSSLVerification', true],
        ];
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

    /**
     * @dataProvider preSetDataProvider
     *
     * @param array $defaultValue
     * @param array $expected
     */
    public function testPreSetData($defaultValue, $expected)
    {
        /** @var CreditCardTypesDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $cardTypesDataProvider */
        $cardTypesDataProvider = $this->createMock(CreditCardTypesDataProviderInterface::class);
        $cardTypesDataProvider
            ->expects(static::any())
            ->method('getDefaultCardTypes')
            ->willReturn(self::CARD_TYPES);

        $formType = new PayPalSettingsType(
            $this->translator,
            $this->encoder,
            $cardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        $settings = new PayPalSettings();
        $settings->setAllowedCreditCardTypes($defaultValue);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects(static::once())
            ->method('getData')
            ->willReturn($settings);

        $formType->preSetData($event);

        static::assertSame($expected, $settings->getAllowedCreditCardTypes());
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'when default value is empty' => [
                'defaultValue' => [],
                'expected' => self::CARD_TYPES,
            ],
            'when default value is not empty' => [
                'defaultValue' => ['visa'],
                'expected' => ['visa'],
            ],
        ];
    }

    /**
     * @TODO remove in v1.3, when CardTypesDataProviderInterface is removed.
     */
    public function testPreSetDataWithDeprecatedInterface()
    {
        /** @var CardTypesDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $cardTypesDataProvider */
        $cardTypesDataProvider = $this->createMock(CardTypesDataProviderInterface::class);

        $formType = new PayPalSettingsType(
            $this->translator,
            $this->encoder,
            $cardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects(static::never())
            ->method('getData');

        $formType->preSetData($event);
    }
}
