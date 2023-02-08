<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestProductItemTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var RequestProductItemType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new RequestProductItemType();
        $this->formType->setDataClass(RequestProductItem::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(RequestProductItem $inputData, array $expectedData = [])
    {
        $form = $this->factory->create(RequestProductItemType::class, $inputData);

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $form->get($key)->getData(), $key);
        }
    }

    public function postSetDataProvider(): array
    {
        return [
            'empty values' => [
                'input' => new RequestProductItem(),
                'expected' => [
                    'quantity' => 1,
                ],
            ],
            'existing values' => [
                'input' => (new RequestProductItem())->setQuantity(10),
                'expected' => [
                    'quantity' => 10,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function submitProvider(): array
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequestProductItem(1, 1),
                'defaultData'   => $this->getRequestProductItem(1),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 1, 'kg', Price::create(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(3, 10, null, Price::create(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(3),
            ],
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price value' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'currency' => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price currency' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'value' => 10,
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg', Price::create(10, null)),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty request product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', Price::create(20, 'USD'))
                    ->setRequestProduct(null),
                'defaultData'   => $this->getRequestProductItem(5)
                    ->setRequestProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', Price::create(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(5),
            ],
        ];
    }

    /**
     * @param int           $id
     * @param ProductUnit[] $productUnits
     * @param string        $unitCode
     *
     * @return RequestProductItem
     */
    protected function createRequestProductItem(
        int $id,
        array $productUnits = [],
        string $unitCode = null
    ): RequestProductItem {
        $productUnit = null;

        $product = new Product();
        foreach ($productUnits as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

            if ($unitCode && $unit->getCode() === $unitCode) {
                $productUnit = $unit;
            }
        }

        $item = $this->createMock(RequestProductItem::class);
        $item->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $item->expects($this->any())
            ->method('getRequestProduct')
            ->willReturn((new RequestProduct())->setProduct($product));
        $item->expects($this->any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $item->expects($this->any())
            ->method('getProductUnitCode')
            ->willReturn($unitCode);

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->preparePriceType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });

        return [
            'oro_product_quantity_unit_precision' => new QuantityUnitPrecisionValidator($roundingService),
        ];
    }
}
