<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class UPSTransportSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\UPSBundle\Entity\UPSTransport';

    /**
     * @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var ShippingOriginProvider |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingOriginProvider;

    /**
     * @var UPSTransportSettingsType
     */
    protected $formType;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $crypter;

    protected function setUp(): void
    {
        /** @var ShippingOriginProvider|\PHPUnit\Framework\MockObject\MockObject $shippingOriginProvider */
        $this->shippingOriginProvider = $this->getMockBuilder(ShippingOriginProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transport = $this->createMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);

        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->formType = new UPSTransportSettingsType(
            $this->transport,
            $this->shippingOriginProvider
        );

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $country = new Country('US');
        $countryType = new EntityTypeStub(['US' => $country], 'oro_country');

        $entityType = new EntityTypeStub(
            [
                1 => $this->getEntity(
                    'Oro\Bundle\UPSBundle\Entity\ShippingService',
                    [
                        'id' => 1,
                        'code' => '01',
                        'description' => 'UPS Next Day Air',
                        'country' => $country
                    ]
                ),
                2 => $this->getEntity(
                    'Oro\Bundle\UPSBundle\Entity\ShippingService',
                    [
                        'id' => 2,
                        'code' => '03',
                        'description' => 'UPS Ground',
                        'country' => $country
                    ]
                ),
            ],
            'entity'
        );

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $localizedFallbackValue = new LocalizedFallbackValueCollectionType($registry);

        return [
            new PreloadedExtension(
                [
                    EntityType::class => $entityType,
                    UPSTransportSettingsType::class => $this->formType,
                    CountryType::class => $countryType,
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => $localizedFallbackValue,
                    new OroEncodedPlaceholderPasswordType($this->crypter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param UPSTransport $defaultData
     * @param array|UPSTransport $submittedData
     * @param bool $isValid
     * @param UPSTransport $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        UPSTransport $defaultData,
        array $submittedData,
        $isValid,
        UPSTransport $expectedData
    ) {
        if (count($submittedData) > 0) {
            $this->crypter
                ->expects($this->once())
                ->method('encryptData')
                ->with($submittedData['upsApiPassword'])
                ->willReturn($submittedData['upsApiPassword']);
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

        $this->shippingOriginProvider
            ->expects(static::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $form = $this->factory->create(UPSTransportSettingsType::class, $defaultData, []);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        static::assertEquals($isValid, $form->isValid());
        static::assertTrue($form->isSynchronized());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        /** @var ShippingService $expectedShippingService */
        $expectedShippingService = $this->getEntity(
            'Oro\Bundle\UPSBundle\Entity\ShippingService',
            [
                'id' => 1,
                'code' => '01',
                'description' => 'UPS Next Day Air',
                'country' => new Country('US')
            ]
        );
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
                    'upsApiUser' => 'user',
                    'upsApiPassword' => 'password',
                    'upsApiKey' => 'key',
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
                    ->setUpsApiUser('user')
                    ->setUpsApiPassword('password')
                    ->setUpsApiKey('key')
                    ->setUpsShippingAccountName('name')
                    ->setUpsShippingAccountNumber('number')
                    ->setUpsPickupType('01')
                    ->setUpsUnitOfWeight('KGS')
                    ->setUpsCountry(new Country('US'))
                    ->addApplicableShippingService($expectedShippingService)
                    ->addLabel((new LocalizedFallbackValue())->setString('first label'))
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => $this->transport->getSettingsEntityFQCN()
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(UPSTransportSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }
}
