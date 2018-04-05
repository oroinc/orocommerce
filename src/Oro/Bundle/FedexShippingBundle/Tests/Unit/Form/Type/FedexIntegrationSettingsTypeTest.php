<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FedexIntegrationSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

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

        $entityType = new EntityTypeStub([
            1 => $this->getEntity(
                FedexShippingService::class,
                [
                    'id' => 1,
                    'code' => '01',
                    'description' => 'UPS Next Day Air',
                ]
            ),
            2 => $this->getEntity(
                FedexShippingService::class,
                [
                    'id' => 2,
                    'code' => '03',
                    'description' => 'UPS Ground',
                ]
            ),
        ]);

        return [
            new PreloadedExtension(
                [
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class =>
                        new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    EntityType::class => $entityType,
                    OroEncodedPlaceholderPasswordType::class => new OroEncodedPlaceholderPasswordType($crypter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmit()
    {
        $submitData = [
            'fedexTestMode' => true,
            'key' => 'key2',
            'password' => 'pass2',
            'accountNumber' => 'num2',
            'meterNumber'=> 'meter2',
            'pickupType' => FedexIntegrationSettings::PICKUP_TYPE_BUSINESS_SERVICE_CENTER,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            'labels' => [
                'values' => ['default' => 'first label'],
            ],
            'shippingServices' => [1, 2],
        ];

        $settings = new FedexIntegrationSettings();
        $settings
            ->setFedexTestMode(false)
            ->setKey('key')
            ->setPassword('pass')
            ->setAccountNumber('num')
            ->setMeterNumber('meter')
            ->setPickupType('pickup')
            ->setUnitOfWeight('unit')
            ->addLabel((new LocalizedFallbackValue())->setString('label'))
            ->addShippingService(new FedexShippingService());

        $form = $this->factory->create(FedexIntegrationSettingsType::class, $settings);

        static::assertSame($settings, $form->getData());

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($settings, $form->getData());
    }

    public function testGetBlockPrefix()
    {
        $formType = new FedexIntegrationSettingsType();
        static::assertSame('oro_fedex_settings', $formType->getBlockPrefix());
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

        $formType = new FedexIntegrationSettingsType();
        $formType->configureOptions($resolver);
    }
}
