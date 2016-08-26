<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UPSBundle\Entity\UPSShippingService;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingServiceType;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class UPSShippingServiceTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\UPSBundle\Entity\UPSShippingService';

    /**
     * @var UPSShippingServiceType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new UPSShippingServiceType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param UPSShippingService $defaultData
     * @param array|UPSShippingService $submittedData
     * @param bool $isValid
     * @param UPSShippingService $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        UPSShippingService $defaultData,
        array $submittedData,
        $isValid,
        UPSShippingService $expectedData
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
                'defaultData'   => new UPSShippingService(),
                'submittedData' => [],
                'isValid' => false,
                'expectedData'  => (new UPSShippingService())
            ],
            'service with value' => [
                'defaultData'   => new UPSShippingService(),
                'submittedData' => [
                    'code' => '03',
                    'description' => 'UPS Ground',
                ],
                'isValid' => true,
                'expectedData'  => (new UPSShippingService())
                    ->setCode('03')
                    ->setDescription('UPS Ground')
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        static::assertEquals(UPSShippingServiceType::NAME, $this->formType->getName());
    }
}
