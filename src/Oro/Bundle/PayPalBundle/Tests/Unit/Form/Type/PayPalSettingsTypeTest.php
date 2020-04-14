<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var PaymentActionsDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentActionsDataProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $creditCardTypesDataProvider = $this->createMock(CreditCardTypesDataProviderInterface::class);
        $creditCardTypesDataProvider->expects($this->any())
            ->method('getCardTypes')
            ->willReturn(self::CARD_TYPES);

        $creditCardTypesDataProvider->expects($this->any())
            ->method('getDefaultCardTypes')
            ->willReturn(self::CARD_TYPES);

        $this->paymentActionsDataProvider = $this->createMock(PaymentActionsDataProviderInterface::class);
        $this->paymentActionsDataProvider->expects($this->any())
            ->method('getPaymentActions')
            ->willReturn([
                self::PAYMENT_ACTION,
                'charge',
            ]);

        $this->formType = new PayPalSettingsType(
            $this->translator,
            $creditCardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        parent::setUp();
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
                    PayPalSettingsType::class => $this->formType,
                    LocalizedFallbackValueCollectionType::class => $localizedType,
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

        $payPalSettings = new PayPalSettings();

        $form = $this->factory->create(PayPalSettingsType::class, $payPalSettings);

        $form->submit($submitData);

        static::assertTrue($form->isValid());
        static::assertTrue($form->isSynchronized());
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
        $form = $this->factory->create(PayPalSettingsType::class, $payPalSettings);

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
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
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
        $creditCardTypesDataProvider = $this->createMock(CreditCardTypesDataProviderInterface::class);
        $creditCardTypesDataProvider
            ->expects(static::any())
            ->method('getDefaultCardTypes')
            ->willReturn(self::CARD_TYPES);

        $formType = new PayPalSettingsType(
            $this->translator,
            $creditCardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        $settings = new PayPalSettings();
        $settings->setAllowedCreditCardTypes($defaultValue);

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
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
}
