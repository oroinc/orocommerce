<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\Mocks\ShippingLineItemInterfaceMock;

class ShippingContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ShippingContext */
    protected $model;

    protected function setUp()
    {
        $this->model = new ShippingContext();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
            [
                ['lineItems', []],
                ['billingAddress', new AddressStub()],
                ['shippingAddress', new AddressStub()],
                ['shippingOrigin', new AddressStub()],
                ['paymentMethod', ''],
                ['currency', ''],
                ['subtotal', new Price()],
            ]
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getFreightClassesProvider
     */
    public function testSetLineItems(array $inputData, array $expectedData)
    {
        $this->model->setLineItems($inputData);

        $this->assertEquals($expectedData, $this->model->getLineItems());
    }

    /**
     * @return array
     */
    public function getFreightClassesProvider()
    {
        $productHolder = new ShippingLineItemInterfaceMock();

        return [
            'no data' => [
                'input' => [
                ],
                'expected' => [
                ],
            ],
            'without interfaces' => [
                'input' => [
                    'no data'
                ],
                'expected' => [
                    new ShippingLineItem()
                ],
            ],
            'ProductHolderInterface' => [
                'input' => [
                    $productHolder
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setProduct($productHolder->getProduct())
                        ->setProductUnit($productHolder->getProductUnit())
                        ->setEntityIdentifier($productHolder->getEntityIdentifier())
                        ->setDimensions($productHolder->getDimensions())
                        ->setQuantity($productHolder->getQuantity())
                        ->setPrice($productHolder->getPrice())
                        ->setWeight($productHolder->getWeight())
                ],
            ],
        ];
    }
}
