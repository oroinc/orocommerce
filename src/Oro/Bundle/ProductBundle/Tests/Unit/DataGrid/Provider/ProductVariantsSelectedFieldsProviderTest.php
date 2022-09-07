<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ProductBundle\DataGrid\Provider\ProductVariantsSelectedFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class ProductVariantsSelectedFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductVariantsSelectedFieldsProvider */
    private $provider;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->provider = new ProductVariantsSelectedFieldsProvider($this->doctrine);
    }

    public function testGetSelectedFields(): void
    {
        $configuration = DatagridConfiguration::create(['name' => 'product-product-variants-edit']);
        $parameters = new ParameterBag(['parentProduct' => 1]);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($this->getEntity(Product::class, ['id' => 1, 'variantFields' => ['variant1', 'variant2']]));

        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $this->assertEquals(['variant1', 'variant2'], $this->provider->getSelectedFields($configuration, $parameters));
    }

    public function testGetSelectedFieldsWithUnsupportedGrid(): void
    {
        $configuration = DatagridConfiguration::create(['name' => 'unsupported-grid']);
        $parameters = new ParameterBag([]);

        $this->assertEmpty($this->provider->getSelectedFields($configuration, $parameters));
    }

    public function testGetSelectedFieldsWithNotFoundProduct(): void
    {
        $configuration = DatagridConfiguration::create(['name' => 'product-product-variants-edit']);
        $parameters = new ParameterBag(['parentProduct' => 1]);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $this->assertEmpty($this->provider->getSelectedFields($configuration, $parameters));
    }
}
