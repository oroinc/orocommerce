<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Form\Type\ShippingServiceType;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ShippingServiceTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\UPSBundle\Entity\ShippingService';

    /**
     * @var ShippingServiceType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new ShippingServiceType();
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
     * @param ShippingService $defaultData
     * @param array|ShippingService $submittedData
     * @param bool $isValid
     * @param ShippingService $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ShippingService $defaultData,
        array $submittedData,
        $isValid,
        ShippingService $expectedData
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
                'defaultData'   => new ShippingService(),
                'submittedData' => [],
                'isValid' => false,
                'expectedData'  => (new ShippingService())
            ],
            'service with value' => [
                'defaultData'   => new ShippingService(),
                'submittedData' => [
                    'code' => '03',
                    'description' => 'UPS Ground',
                ],
                'isValid' => true,
                'expectedData'  => (new ShippingService())
                    ->setCode('03')
                    ->setDescription('UPS Ground')
            ]
        ];
    }

    public function testGetName()
    {
        static::assertEquals(ShippingServiceType::NAME, $this->formType->getName());
    }

    public function getBlockPrefix()
    {
        static::assertEquals(ShippingServiceType::NAME, $this->formType->getBlockPrefix());
    }
}
