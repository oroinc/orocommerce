<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class FedexIntegrationSettingsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FedexIntegrationSettingsType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FedexIntegrationSettingsType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter
            ->expects(static::any())
            ->method('encryptData')
            ->willReturn('encrypted');

        return [
            new PreloadedExtension(
                [
                    new LocalizedPropertyType(),
                    new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    new OroEncodedPlaceholderPasswordType($crypter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmitValid()
    {
        $submitData = [
            'key' => 'key2',
            'password' => 'pass2',
            'accountNumber' => 'num2',
            'meterNumber'=> 'meter2',
            'pickupType' => FedexIntegrationSettings::PICKUP_TYPE_BUSINESS_SERVICE_CENTER,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            'labels' => [
                'values' => ['default' => 'first label'],
            ],
        ];

        $settings = new FedexIntegrationSettings();
        $settings
            ->setKey('key')
            ->setPassword('pass')
            ->setAccountNumber('num')
            ->setMeterNumber('meter')
            ->setPickupType('pickup')
            ->setUnitOfWeight('unit')
            ->addLabel((new LocalizedFallbackValue())->setString('label'));

        $form = $this->factory->create($this->formType, $settings);

        static::assertSame($settings, $form->getData());

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($settings, $form->getData());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame('oro_fedex_settings', $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver
            ->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => FedexIntegrationSettings::class
            ]);

        $this->formType->configureOptions($resolver);
    }
}
