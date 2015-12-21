<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
            ]
        );
    }

    public function testDuplicate()
    {
        $this->client->followRedirects(true);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->executeAction($product, 'orob2b_product_duplicate_action');

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());
        $content = $result->getContent();
        $this->assertContains('redirectUrl', $content);

        $resultData = json_decode($content, true);
        $this->assertArrayHasKey('redirectUrl', $resultData);
        $flashMessages = self::getContainer()->get('session')->getFlashBag()->all();
        $this->assertEquals(['success' => [0 => 'Product has been duplicated']], $flashMessages);
    }

    /**
     * @param Product $product
     * @param string $actionName
     */
    protected function executeAction(Product $product, $actionName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_action_execute',
                [
                    'actionName' => $actionName,
                    'route' => 'orob2b_product_view',
                    'entityId' => $product->getId(),
                    'entityClass' => 'OroB2B\Bundle\ProductBundle\Entity\Product'
                ]
            )
        );
    }
}
