<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Provider;

use Oro\Bundle\InventoryBundle\Provider\InventoryStatusProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class InventoryStatusProviderTest extends WebTestCase
{
    private InventoryStatusProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
        ]);
        $this->provider = $this->getClientContainer()->get('oro_inventory.provider.inventory_status');
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testGetForProduct(string $reference, string $label, string $code): void
    {
        /** @var Product $data */
        $data = $this->getReference($reference);
        self::assertEquals($label, $this->provider->getLabel($data));
        self::assertEquals($code, $this->provider->getCode($data));
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testGetForProductView(string $reference, string $label, string $code): void
    {
        /** @var Product $product */
        $product = $this->getReference($reference);

        $data = new ProductView();
        $data->set('id', $product->getId());
        self::assertEquals($label, $this->provider->getLabel($data));
        self::assertEquals($code, $this->provider->getCode($data));
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testGetForSearchResult(string $reference, string $label, string $code): void
    {
        /** @var Product $product */
        $product = $this->getReference($reference);

        $data = ['id' => $product->getId()];
        self::assertEquals($label, $this->provider->getLabel($data));
        self::assertEquals($code, $this->provider->getCode($data));
    }

    public function productDataProvider(): \Generator
    {
        yield [
            'reference' => LoadProductData::PRODUCT_1,
            'label' => 'In Stock',
            'code' => 'prod_inventory_status.in_stock',
        ];
        yield [
            'reference' => LoadProductData::PRODUCT_3,
            'label' => 'Out of Stock',
            'code' => 'prod_inventory_status.out_of_stock',
        ];
        yield [
            'reference' => LoadProductData::PRODUCT_4,
            'label' => 'Discontinued',
            'code' => 'prod_inventory_status.discontinued',
        ];
    }

    public function testGetForProductException()
    {
        // check if product has no inventory status assigned
        $data = new Product();
        self::assertTrue(null == $this->provider->getLabel($data));
        self::assertTrue(null == $this->provider->getCode($data));
    }

    public function testGetForProductViewException(): void
    {
        $data = new ProductView();
        $data->set('id', -1);
        self::assertTrue(null == $this->provider->getLabel($data));
        self::assertTrue(null == $this->provider->getCode($data));
    }

    public function testGetForSearchResultException(): void
    {
        $data = [];
        self::assertTrue(null == $this->provider->getLabel($data));
        self::assertTrue(null == $this->provider->getCode($data));
    }
}
