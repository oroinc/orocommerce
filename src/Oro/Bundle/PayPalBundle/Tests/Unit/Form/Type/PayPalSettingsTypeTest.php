<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayPalSettingsTypeTest extends FormIntegrationTestCase
{
    private const CARD_TYPES = [
        'visa',
        'mastercard',
    ];

    private const PAYMENT_ACTION = 'authorize';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PaymentActionsDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentActionsDataProvider;

    /** @var PayPalSettingsType */
    private $formType;

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
            ->willReturn([self::PAYMENT_ACTION, 'charge']);

        $this->formType = new PayPalSettingsType(
            $this->translator,
            $creditCardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                PayPalSettingsType::class => $this->formType,
                LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
            ], [
                FormType::class => [new TooltipFormExtensionStub($this)],
            ]),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetBlockPrefixReturnsCorrectString()
    {
        self::assertSame(PayPalSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
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

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($payPalSettings, $form->getData());
    }

    /**
     * @dataProvider defaultValuesAreSetDataProvider
     */
    public function testDefaultValuesAreSet(string $property, mixed $value)
    {
        $payPalSettings = new PayPalSettings();
        $form = $this->factory->create(PayPalSettingsType::class, $payPalSettings);

        self::assertEquals($value, $form->get($property)->getData());
        self::assertEquals($payPalSettings, $form->getData());
    }

    public function defaultValuesAreSetDataProvider(): array
    {
        return [
            ['allowedCreditCardTypes', self::CARD_TYPES],
            ['requireCVVEntry', true],
            ['enableSSLVerification', true],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'data_class' => PayPalSettings::class
            ]);

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(array $defaultValue, array $expected)
    {
        $creditCardTypesDataProvider = $this->createMock(CreditCardTypesDataProviderInterface::class);
        $creditCardTypesDataProvider->expects(self::any())
            ->method('getDefaultCardTypes')
            ->willReturn(self::CARD_TYPES);

        $formType = new PayPalSettingsType(
            $this->translator,
            $creditCardTypesDataProvider,
            $this->paymentActionsDataProvider
        );

        $settings = new PayPalSettings();
        $settings->setAllowedCreditCardTypes($defaultValue);

        $event = $this->createMock(FormEvent::class);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($settings);

        $formType->preSetData($event);

        self::assertSame($expected, $settings->getAllowedCreditCardTypes());
    }

    public function preSetDataProvider(): array
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
