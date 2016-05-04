<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginWarehouseType;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

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
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $options
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null, $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty data' => [
                'isValid' => false,
                'submittedData' => [],
                'expectedData' => $this->getShippingOriginWarehouse(),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'empty warehouse' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'warehouse'),
                'defaultData' => $this->getShippingOriginWarehouse(false, 'warehouse'),
            ],
            'empty country' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'country'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'empty region' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'region'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'empty postalCode' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'city' => 'city1',
                    'street' => 'street1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'postalCode'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'empty city' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'street' => 'street1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'city'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'empty street' => [
                'isValid' => false,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true, 'street'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'full data' => [
                'isValid' => true,
                'submittedData' => [
                    'system' => false,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                    'street2' => 'street2',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true)
                    ->setStreet2('street2'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
            'full data and system' => [
                'isValid' => true,
                'submittedData' => [
                    'system' => true,
                    'country' => 'US',
                    'region' => 'US-AL',
                    'postalCode' => 'code1',
                    'city' => 'city1',
                    'street' => 'street1',
                    'street2' => 'street2',
                ],
                'expectedData' => $this->getShippingOriginWarehouse(true)
                    ->setSystem(true)
                    ->setStreet2('street2'),
                'defaultData' => $this->getShippingOriginWarehouse(),
            ],
        ];
    }

    /**
     * @param bool $fill
     * @param string $exclude
     * @return ShippingOriginWarehouse
     */
    protected function getShippingOriginWarehouse($fill = false, $exclude = '')
    {
        $shippingOriginWarehouse = new ShippingOriginWarehouse();

        if ($exclude !== 'warehouse') {
            $shippingOriginWarehouse->setWarehouse(new Warehouse());
        }

        if ($fill) {
            if ($exclude !== 'country') {
                $shippingOriginWarehouse->setCountry(new Country('US'));
            }

            if ($exclude !== 'region') {
                $region = new Region('US-AL');
                $region->setCountry(new Country('US'));

                $shippingOriginWarehouse->setRegion($region);
            }

            if ($exclude !== 'postalCode') {
                $shippingOriginWarehouse->setPostalCode('code1');
            }

            if ($exclude !== 'city') {
                $shippingOriginWarehouse->setCity('city1');
            }

            if ($exclude !== 'street') {
                $shippingOriginWarehouse->setStreet('street1');
            }
        }

        return $shippingOriginWarehouse;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $shippingOriginType = new ShippingOriginType(new AddressCountryAndRegionSubscriberStub());
        $shippingOriginType->setDataClass('OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse');

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $shippingOriginType->getName() => $shippingOriginType,
                    ],
                    []
                ),
                $this->getValidatorExtension(true)
            ]
        );
    }
}
