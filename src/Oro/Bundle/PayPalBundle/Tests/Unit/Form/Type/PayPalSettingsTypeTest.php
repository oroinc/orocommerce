<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
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
        parent::setUp();

        $this->formType = new PayPalSettingsType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $configManager = $this->createMock(ConfigManager::class);

        return [
            new PreloadedExtension(
                [
                    new LocalizedPropertyType(),
                    new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($registry),
                    new EnumSelectType($configManager, $registry),
                    new Select2Type('translatable_entity'),
                    new TranslatableEntityType($registry),
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
            'creditCardLabels' => ['values' => ['default' => 'creditCard']],
            'creditCardShortLabels' => ['values' => ['default' => 'creditCardShort']],
            'expressCheckoutLabels' => ['values' => ['default' => 'expressCheckout']],
            'expressCheckoutShortLabels' => ['values' => ['default' => 'expressCheckoutShort']],
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
