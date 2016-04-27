<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginWarehouseType;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginWarehouseTypeTest extends AddressFormExtensionTestCase
{
    /** @var ShippingOriginWarehouseType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ShippingOriginWarehouseType();
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingOriginWarehouseType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ShippingOriginType::NAME, $this->formType->getParent());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            [
                'submittedData' => [
                    'system' => true
                ],
                'expectedData' => [
                    'system' => true
                ]
            ],
            [
                'submittedData' => [
                    'system' => false
                ],
                'expectedData' => [
                    'system' => false
                ]
            ]
        ];
    }

    /**
     * @param bool $system
     * @return ShippingOrigin
     */
    protected function getShippingOrigin($system)
    {
        $shippingOrigin = new ShippingOrigin();
        $shippingOrigin->setSystem($system);

        return $shippingOrigin;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        ShippingOriginType::NAME => new ShippingOriginType(new AddressCountryAndRegionSubscriberStub()),
                        $this->getValidatorExtension(true)
                    ],
                    []
                )
            ]
        );
    }
}
