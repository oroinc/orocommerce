<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
            ]
        );
    }

    public function testDuplicate()
    {
        $this->client->followRedirects(true);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->executeOperation($product, 'oro_product_duplicate');

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $content = $result->getContent();
        $this->assertContains('redirectUrl', $content);

        $resultData = json_decode($content, true);
        $this->assertArrayHasKey('redirectUrl', $resultData);

        $flashMessages = self::getContainer()->get('session')->getFlashBag()->all();
        $this->assertEquals(['success' => [0 => 'Product has been duplicated']], $flashMessages);
    }

    /**
     * @param Product $product
     * @param string $operationName
     */
    protected function executeOperation(Product $product, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_product_view',
                    'entityId' => $product->getId(),
                    'entityClass' => 'Oro\Bundle\ProductBundle\Entity\Product'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
