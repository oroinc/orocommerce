<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataGrid\Extension\MassAction;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductDeleteMassActionHandlerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadProductKitData::class]);
    }

    public function testMassDeleteActionTryToDeleteProductWithProductKitItems(): void
    {
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $id = $product->getId();

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl(
                'oro_datagrid_mass_action',
                [
                    'gridName' => 'products-grid',
                    'actionName' => 'delete',
                    'values' => $id,
                ]
            )
        );

        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertFalse($data['successful']);
        self::assertSame($data['count'], 0);

        self::assertNotNull(
            self::getContainer()->get('doctrine')->getManagerForClass(Product::class)->find(Product::class, $id)
        );
    }

    public function testMassDeleteActionToDeleteProductWithoutProductKitItems(): void
    {
        $product = $this->getReference(LoadProductData::PRODUCT_6);
        $id = $product->getId();

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl(
                'oro_datagrid_mass_action',
                [
                    'gridName' => 'products-grid',
                    'actionName' => 'delete',
                    'values' => $id,
                ]
            )
        );

        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertTrue($data['successful']);
        self::assertSame($data['count'], 1);

        self::assertNull(
            self::getContainer()->get('doctrine')->getManagerForClass(Product::class)->find(Product::class, $id)
        );
    }
}
