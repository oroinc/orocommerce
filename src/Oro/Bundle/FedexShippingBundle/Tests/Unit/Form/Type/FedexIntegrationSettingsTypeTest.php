<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UpdateIntegrationValidator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FedexIntegrationSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var FedexResponseCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fedexResponseCache;

    /** @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceCache;

    #[\Override]
    protected function getValidators(): array
    {
        return [
            'oro_fedex_shipping_remove_used_shipping_service_validator' => new UpdateIntegrationValidator(
                $this->createMock(IntegrationShippingMethodFactoryInterface::class),
                $this->createMock(ShippingMethodValidatorInterface::class),
                'shippingServices'
            ),
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $this->fedexResponseCache = $this->createMock(FedexResponseCacheInterface::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);

        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects(self::any())
            ->method('encryptData')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getFedexShippingService(1, '01', 'UPS Next Day Air'),
                        2 => $this->getFedexShippingService(2, '03', 'UPS Ground'),
                    ]),
                    new OroEncodedPlaceholderPasswordType($crypter),
                    new FedexIntegrationSettingsType($this->fedexResponseCache, $this->shippingPriceCache)
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    private function getFedexShippingService(int $id, string $code, string $description): FedexShippingService
    {
        $service = new FedexShippingService();
        ReflectionUtil::setId($service, $id);
        $service->setCode($code);
        $service->setDescription($description);

        return $service;
    }

    public function testSubmit(): void
    {
        $submitData = [
            'fedexTestMode' => true,
            'clientId' => 'key2',
            'clientSecret' => 'pass2',
            'accountNumber' => 'num2',
            'pickupType' => FedexIntegrationSettings::PICKUP_CONTACT_FEDEX_TO_SCHEDULE,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            'labels' => [
                'values' => ['default' => 'first label'],
            ],
            'shippingServices' => [1, 2],
        ];

        $settings = new FedexIntegrationSettings();
        $settings
            ->setFedexTestMode(false)
            ->setClientId('key')
            ->setClientSecret('pass')
            ->setAccountNumber('num')
            ->setPickupType('pickup')
            ->setUnitOfWeight('unit')
            ->setIgnorePackageDimensions(true)
            ->addLabel((new LocalizedFallbackValue())->setString('label'))
            ->addShippingService(new FedexShippingService());

        $form = $this->factory->create(FedexIntegrationSettingsType::class, $settings);

        self::assertSame($settings, $form->getData());

        $this->fedexResponseCache->expects($this->once())
            ->method('deleteAll');
        $this->shippingPriceCache->expects($this->once())
            ->method('deleteAllPrices');

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($settings, $form->getData());
    }

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $submitData = array_replace_recursive([
            'fedexTestMode' => true,
            'clientId' => 'key2',
            'clientSecret' => 'pass2',
            'accountNumber' => 'num2',
            'pickupType' => FedexIntegrationSettings::PICKUP_CONTACT_FEDEX_TO_SCHEDULE,
            'unitOfWeight' => FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
            'labels' => [
                'values' => ['default' => 'first label'],
            ],
            'shippingServices' => [1, 2],
        ], $override);

        $form = $this->factory->create(FedexIntegrationSettingsType::class, new FedexIntegrationSettings());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'label too long' => [['labels' => ['values' => ['default' => str_repeat('a', 256)]]]],
            'clientId too long' => [['clientId' => str_repeat('a', 256)]],
            'clientSecret too long' => [['clientSecret' => str_repeat('a', 256)]],
            'accountNumber too long' => [['accountNumber' => str_repeat('a', 101)]],
        ];
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new FedexIntegrationSettingsType(
            $this->fedexResponseCache,
            $this->shippingPriceCache
        );
        self::assertSame('oro_fedex_settings', $formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => FedexIntegrationSettings::class]);

        $formType = new FedexIntegrationSettingsType(
            $this->fedexResponseCache,
            $this->shippingPriceCache
        );
        $formType->configureOptions($resolver);
    }
}
