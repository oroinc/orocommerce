<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;

class ProductListFormAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductFormAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productFormAvailabilityProvider;

    /** @var ProductListFormAvailabilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);

        $this->provider = new ProductListFormAvailabilityProvider(
            $this->productFormAvailabilityProvider
        );
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormType(?string $matrixFormType, string $expectedMatrixFormType): void
    {
        $product = new ProductView();
        $product->set('id', 123);
        $product->set('type', Product::TYPE_CONFIGURABLE);
        $product->set('unit', 'set');
        $product->set('variant_fields_count', 1);

        $availableMatrixFormTypes = [];
        if (null !== $matrixFormType) {
            $availableMatrixFormTypes = [$product->getId() => $matrixFormType];
        }
        $this->productFormAvailabilityProvider->expects(self::once())
            ->method('getAvailableMatrixFormTypes')
            ->with([$product->getId() => [$product->get('unit'), $product->get('variant_fields_count')]])
            ->willReturn($availableMatrixFormTypes);

        self::assertSame($expectedMatrixFormType, $this->provider->getAvailableMatrixFormType($product));
    }

    /**
     * @dataProvider getAvailableMatrixFormTypeDataProvider
     */
    public function testGetAvailableMatrixFormTypes(?string $matrixFormType, string $expectedMatrixFormType): void
    {
        $productId = 123;
        $product = new ProductView();
        $product->set('id', $productId);
        $product->set('type', Product::TYPE_CONFIGURABLE);
        $product->set('unit', 'set');
        $product->set('variant_fields_count', 1);

        $availableMatrixFormTypes = [];
        if (null !== $matrixFormType) {
            $availableMatrixFormTypes = [$product->getId() => $matrixFormType];
        }
        $this->productFormAvailabilityProvider->expects(self::once())
            ->method('getAvailableMatrixFormTypes')
            ->with([$product->getId() => [$product->get('unit'), $product->get('variant_fields_count')]])
            ->willReturn($availableMatrixFormTypes);

        self::assertSame(
            [$productId => $expectedMatrixFormType],
            $this->provider->getAvailableMatrixFormTypes([$product])
        );
    }

    public function getAvailableMatrixFormTypeDataProvider(): array
    {
        return [
            'matrix unknown' => [
                'matrixFormType'         => null,
                'expectedMatrixFormType' => Configuration::MATRIX_FORM_NONE
            ],
            'matrix none'    => [
                'matrixFormType'         => Configuration::MATRIX_FORM_NONE,
                'expectedMatrixFormType' => Configuration::MATRIX_FORM_NONE
            ],
            'matrix inline'  => [
                'matrixFormType'         => Configuration::MATRIX_FORM_INLINE,
                'expectedMatrixFormType' => Configuration::MATRIX_FORM_INLINE
            ],
            'matrix popup'   => [
                'matrixFormType'         => Configuration::MATRIX_FORM_POPUP,
                'expectedMatrixFormType' => Configuration::MATRIX_FORM_POPUP
            ],
        ];
    }

    public function testGetAvailableMatrixFormTypeForNotConfigurableProduct(): void
    {
        $product = new ProductView();
        $product->set('id', 123);
        $product->set('type', Product::TYPE_SIMPLE);

        $this->productFormAvailabilityProvider->expects(self::never())
            ->method('getAvailableMatrixFormTypes');

        self::assertSame(
            Configuration::MATRIX_FORM_NONE,
            $this->provider->getAvailableMatrixFormType($product)
        );
    }

    public function testGetAvailableMatrixFormTypesForNotConfigurableProduct(): void
    {
        $productId = 123;
        $product = new ProductView();
        $product->set('id', $productId);
        $product->set('type', Product::TYPE_SIMPLE);

        $this->productFormAvailabilityProvider->expects(self::never())
            ->method('getAvailableMatrixFormTypes');

        self::assertSame(
            [$productId => Configuration::MATRIX_FORM_NONE],
            $this->provider->getAvailableMatrixFormTypes([$product])
        );
    }
}
