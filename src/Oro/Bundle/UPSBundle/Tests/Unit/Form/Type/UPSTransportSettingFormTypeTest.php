<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\ShippingServiceCollectionType;
use Oro\Bundle\UPSBundle\Form\Type\ShippingServiceType;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingFormType;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class UPSTransportSettingFormTypeTest extends FormIntegrationTestCase
{
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
        $this->transport = $this->getMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);
        $this->formType = new UPSTransportSettingFormType($this->transport);
        $this->formType->setDataClass(self::DATA_CLASS);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $shippingService = new ShippingServiceType();
        $shippingService->setDataClass('Oro\Bundle\UPSBundle\Entity\ShippingService');
        return [
            new PreloadedExtension(
                [
                    OroCollectionType::NAME => new OroCollectionType(),
                    ShippingServiceCollectionType::NAME => new ShippingServiceCollectionType(),
                    ShippingServiceType::NAME => $shippingService,
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
                    'applicableShippingServices' => [
                        [
                            'code' => '03',
                            'description' => 'UPS Ground'
                        ]
                    ]
                ],
                'isValid' => true,
                'expectedData'  => (new UPSTransport())
                    ->setBaseUrl('http://ups.com')
                    ->setApiUser('user')
                    ->setApiPassword('password')
                    ->setApiKey('key')
                    ->setShippingAccountName('name')
                    ->setShippingAccountNumber('number')
                    ->addApplicableShippingService(
                        (new ShippingService())->setCode('03')->setDescription('UPS Ground')
                    )
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
