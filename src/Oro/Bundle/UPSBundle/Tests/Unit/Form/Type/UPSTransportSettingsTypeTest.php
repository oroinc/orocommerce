<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class UPSTransportSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\UPSBundle\Entity\UPSTransport';

    /**
     * @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var ShippingOriginProvider |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingOriginProvider;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /**
     * @var UPSTransportSettingsType
     */
    protected $formType;

    protected function setUp()
    {
        /** @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject $shippingOriginProvider */
        $this->shippingOriginProvider = $this->getMockBuilder(ShippingOriginProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transport = $this->getMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new UPSTransportSettingsType(
            $this->transport,
            $this->shippingOriginProvider,
            $this->doctrineHelper
        );

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
        ];

        $translatableEntity->expects(static::any())->method('setDefaultOptions')->will(
            static::returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );
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
        return [
            new PreloadedExtension(
                [
                    'entity' =>$entityType,
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $translatableEntity,
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
        $shippingOrigin = new ShippingOrigin(
            [
                'country'     => 'US',
                'region'      => 'test',
                'region_text' => 'test region text',
                'postal_code' => 'test postal code',
                'city'        => 'test city',
                'street'      => 'test street 1',
                'street2'     => 'test street 2'
            ]
        );

        $this->shippingOriginProvider
            ->expects(static::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects(static::once())
            ->method('findOneBy')
            ->willReturn(new Country('US'));

        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects(static::once())
            ->method('getRepository')
            ->with('OroAddressBundle:Country')
            ->willReturn($repository);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityManagerForClass')
            ->with('OroAddressBundle:Country')
            ->willReturn($entityManager);

        $form = $this->factory->create($this->formType, $defaultData, []);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        static::assertEquals($isValid, $form->isValid());
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
                'defaultData'   => new UPSTransport(),
                'submittedData' => [],
                'isValid' => true,
                'expectedData'  => (new UPSTransport())
            ],
            'service with value' => [
                'defaultData'   => new UPSTransport(),
                'submittedData' => [
                    'baseUrl' => 'http://ups.com',
                    'apiUser' => 'user',
                    'apiPassword' => 'password',
                    'apiKey'=> 'key',
                    'shippingAccountName' => 'name',
                    'shippingAccountNumber' => 'number',
                    'pickupType' => '01',
                    'unitOfWeight' => 'KGS',
                    'country' => 'US',
                    'applicableShippingServices' => [1]
                ],
                'isValid' => true,
                'expectedData'  => (new UPSTransport())
                    ->setBaseUrl('http://ups.com')
                    ->setApiUser('user')
                    ->setApiPassword('password')
                    ->setApiKey('key')
                    ->setShippingAccountName('name')
                    ->setShippingAccountNumber('number')
                    ->setPickupType('01')
                    ->setUnitOfWeight('KGS')
                    ->setCountry(new Country('US'))
                    ->addApplicableShippingService($expectedShippingService)
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => $this->transport->getSettingsEntityFQCN()
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(UPSTransportSettingsType::NAME, $this->formType->getBlockPrefix());
    }
}
