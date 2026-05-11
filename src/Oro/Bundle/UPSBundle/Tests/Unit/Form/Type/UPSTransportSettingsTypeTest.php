<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UpdateIntegrationValidator;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Validator\Constraints\CountryShippingServicesValidator;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UPSTransportSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var SystemShippingOriginProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $systemShippingOriginProvider;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var UPSTransportSettingsType */
    private $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->systemShippingOriginProvider = $this->createMock(SystemShippingOriginProvider::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects(self::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(UPSTransport::class);

        $this->formType = new UPSTransportSettingsType(
            $transport,
            $this->systemShippingOriginProvider
        );

        parent::setUp();
    }

    #[\Override]
    protected function getValidators(): array
    {
        return [
            'oro_ups_remove_used_shipping_service_validator' => new UpdateIntegrationValidator(
                $this->createMock(IntegrationShippingMethodFactoryInterface::class),
                $this->createMock(ShippingMethodValidatorInterface::class),
                'applicableShippingServices'
            ),
            CountryShippingServicesValidator::class => new CountryShippingServicesValidator(),
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $country = new Country('US');

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getShippingService(1, '01', 'UPS Next Day Air', $country),
                        2 => $this->getShippingService(2, '03', 'UPS Ground', $country)
                    ]),
                    CountryType::class => new EntityTypeStub(['US' => $country]),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    new OroEncodedPlaceholderPasswordType($this->crypter),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    private function getShippingService(
        int $id,
        string $code,
        string $description,
        Country $country,
    ): ShippingService {
        $shippingService = new ShippingService();
        ReflectionUtil::setId($shippingService, $id);
        $shippingService->setCode($code);
        $shippingService->setDescription($description);
        $shippingService->setCountry($country);

        return $shippingService;
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        UPSTransport $defaultData,
        array $submittedData,
        bool $isValid,
        UPSTransport $expectedData
    ) {
        if (count($submittedData) > 0) {
            $this->crypter->expects($this->once())
                ->method('encryptData')
                ->with($submittedData['upsClientSecret'])
                ->willReturn($submittedData['upsClientSecret']);
        }

        $shippingOrigin = new ShippingOrigin(
            [
                'country' => new Country('US'),
                'region' => 'test',
                'region_text' => 'test region text',
                'postal_code' => 'test postal code',
                'city' => 'test city',
                'street' => 'test street 1',
                'street2' => 'test street 2'
            ]
        );

        $this->systemShippingOriginProvider->expects(self::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $form = $this->factory->create(UPSTransportSettingsType::class, $defaultData, []);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertEquals($isValid, $form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'service without value' => [
                'defaultData' => new UPSTransport(),
                'submittedData' => [],
                'isValid' => false,
                'expectedData' => (new UPSTransport())
                    ->addLabel(new LocalizedFallbackValue())
            ],
            'service with value' => [
                'defaultData' => new UPSTransport(),
                'submittedData' => [
                    'labels' => [
                        'values' => [ 'default' => 'first label'],
                    ],
                    'upsTestMode' => true,
                    //'upsApiUser' => 'user',
                    //'upsApiPassword' => 'password',
                    //'upsApiKey' => 'key',
                    'upsClientId' => 'client_id',
                    'upsClientSecret' => 'client_secret',
                    'upsShippingAccountName' => 'name',
                    'upsShippingAccountNumber' => 'number',
                    'upsPickupType' => '01',
                    'upsUnitOfWeight' => 'KGS',
                    'upsCountry' => 'US',
                    'applicableShippingServices' => [1]
                ],
                'isValid' => true,
                'expectedData' => (new UPSTransport())
                    ->setUpsTestMode(true)
                    //->setUpsApiUser('user')
                    //->setUpsApiPassword('password')
                    //->setUpsApiKey('key')
                    ->setUpsClientId('client_id')
                    ->setUpsClientSecret('client_secret')
                    ->setUpsShippingAccountName('name')
                    ->setUpsShippingAccountNumber('number')
                    ->setUpsPickupType('01')
                    ->setUpsUnitOfWeight('KGS')
                    ->setUpsCountry(new Country('US'))
                    ->addApplicableShippingService(
                        $this->getShippingService(1, '01', 'UPS Next Day Air', new Country('US'))
                    )
                    ->addLabel((new LocalizedFallbackValue())->setString('first label'))
            ]
        ];
    }

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $this->crypter->expects(self::any())
            ->method('encryptData')
            ->willReturnArgument(0);

        $this->systemShippingOriginProvider->expects(self::once())
            ->method('getSystemShippingOrigin')
            ->willReturn(new ShippingOrigin([
                'country' => new Country('US'),
                'region' => 'test',
                'region_text' => 'test',
                'postal_code' => 'test',
                'city' => 'test',
                'street' => 'test',
                'street2' => 'test',
            ]));

        $submitData = array_replace_recursive([
            'labels' => ['values' => ['default' => 'first label']],
            'upsTestMode' => true,
            'upsClientId' => 'client_id',
            'upsClientSecret' => 'client_secret',
            'upsShippingAccountName' => 'name',
            'upsShippingAccountNumber' => 'number',
            'upsPickupType' => '01',
            'upsUnitOfWeight' => 'KGS',
            'upsCountry' => 'US',
            'applicableShippingServices' => [1],
        ], $override);

        $form = $this->factory->create(UPSTransportSettingsType::class, new UPSTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'label too long' => [['labels' => ['values' => ['default' => str_repeat('a', 256)]]]],
            'upsClientId too long' => [['upsClientId' => str_repeat('a', 256)]],
            'upsClientSecret too long' => [['upsClientSecret' => str_repeat('a', 256)]],
            'upsShippingAccountName too long' => [['upsShippingAccountName' => str_repeat('a', 256)]],
            'upsShippingAccountNumber too long' => [['upsShippingAccountNumber' => str_repeat('a', 101)]],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => UPSTransport::class]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals('oro_ups_transport_settings', $this->formType->getBlockPrefix());
    }
}
