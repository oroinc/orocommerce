<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
     * @var FedexResponseCacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fedexResponseCache;

    /**
     * @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingPriceCache;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        $this->fedexResponseCache = $this->createMock(FedexResponseCacheInterface::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);

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

        $formType = new FedexIntegrationSettingsType(
            $this->fedexResponseCache,
            $this->shippingPriceCache
        );

        return [
            new PreloadedExtension(
                [
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    EntityType::class => $entityType,
                    new OroEncodedPlaceholderPasswordType($crypter),
                    FedexIntegrationSettingsType::class => $formType
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                    ]
                ]
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
            'meterNumber' => 'meter2',
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
            ->setIgnorePackageDimensions(true)
            ->addLabel((new LocalizedFallbackValue())->setString('label'))
            ->addShippingService(new FedexShippingService());

        $form = $this->factory->create(FedexIntegrationSettingsType::class, $settings);

        static::assertSame($settings, $form->getData());

        $this->fedexResponseCache->expects($this->once())
            ->method('deleteAll')
            ->with($settings);
        $this->shippingPriceCache->expects($this->once())
            ->method('deleteAllPrices');

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($settings, $form->getData());
    }

    public function testGetBlockPrefix()
    {
        $formType = new FedexIntegrationSettingsType(
            $this->fedexResponseCache,
            $this->shippingPriceCache
        );
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

        $formType = new FedexIntegrationSettingsType(
            $this->fedexResponseCache,
            $this->shippingPriceCache
        );
        $formType->configureOptions($resolver);
    }
}
