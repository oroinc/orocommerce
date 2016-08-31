<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingFormType;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class UPSTransportSettingFormTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\UPSBundle\Entity\UPSTransport';

    /**
     * @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var UPSTransportSettingFormType
     */
    protected $formType;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transport = $this->getMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);
        $this->formType = new UPSTransportSettingFormType($this->transport, $configManager);
        $this->formType->setDataClass(self::DATA_CLASS);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $country = new Country('US');
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
                    'entity' =>$entityType
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
                'isValid' => false,
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
                    ->addApplicableShippingService($expectedShippingService)
            ]
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => $this->transport->getSettingsEntityFQCN()
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        static::assertEquals(UPSTransportSettingFormType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(UPSTransportSettingFormType::NAME, $this->formType->getBlockPrefix());
    }
}
