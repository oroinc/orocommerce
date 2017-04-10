<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Form\Type\AuthorizeNetSettingsType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

class AuthorizeNetSettingsTypeTest extends FormIntegrationTestCase
{
    const CARD_TYPES = [
        'visa',
        'mastercard',
    ];
    const PAYMENT_ACTION = 'authorize';

    /**
     * @var AuthorizeNetSettingsType
     */
    private $formType;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $encoder;

    public function setUp()
    {
        parent::setUp();
        $this->prepareForm();
    }

    protected function prepareForm()
    {
        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        /** @var CardTypesDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $cardTypesDataProvider */
        $cardTypesDataProvider = $this->createMock(CardTypesDataProviderInterface::class);
        $cardTypesDataProvider->expects($this->any())
            ->method('getCardTypes')
            ->willReturn(self::CARD_TYPES);

        /** @var PaymentActionsDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $actionsDataProvider */
        $actionsDataProvider = $this->createMock(PaymentActionsDataProviderInterface::class);
        $actionsDataProvider->expects($this->any())
            ->method('getPaymentActions')
            ->willReturn(
                [
                    self::PAYMENT_ACTION,
                    'charge',
                ]
            );

        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->formType = new AuthorizeNetSettingsType(
            $translator,
            $this->encoder,
            $cardTypesDataProvider,
            $actionsDataProvider
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
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testGetBlockPrefixReturnsCorrectString()
    {
        static::assertSame(AuthorizeNetSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testSubmit()
    {
        $submitData = [
            'creditCardLabels' => [['string' => 'creditCard']],
            'creditCardShortLabels' => [['string' => 'creditCardShort']],
            'allowedCreditCardTypes' => self::CARD_TYPES,
            'creditCardPaymentAction' => self::PAYMENT_ACTION,
            'apiLoginId' => 'some login',
            'transactionKey' => 'some transaction key',
            'clientKey' => 'some client key',
            'testMode' => true,
        ];

        $this->encoder
            ->expects(static::any())
            ->method('encryptData')
            ->willReturnMap(
                [
                    [$submitData['apiLoginId'], $submitData['apiLoginId']],
                    [$submitData['transactionKey'], $submitData['transactionKey']],
                    [$submitData['clientKey'], $submitData['clientKey']],
                ]
            );

        $authorizeNetSettings = new AuthorizeNetSettings();

        $form = $this->factory->create($this->formType, $authorizeNetSettings);

        $form->submit($submitData);

        static::assertTrue($form->isValid());
        static::assertEquals($authorizeNetSettings, $form->getData());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => AuthorizeNetSettings::class,
                ]
            );

        $this->formType->configureOptions($resolver);
    }
}
