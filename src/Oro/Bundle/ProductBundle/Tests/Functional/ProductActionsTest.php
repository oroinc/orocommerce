<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductActionsTest extends WebTestCase
{
    use OperationAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadProductData::class
            ]
        );
    }

    public function testDuplicate(): void
    {
        $this->client->followRedirects(true);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->executeOperation($product, 'oro_product_duplicate');

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $content = $result->getContent();
        self::assertStringContainsString('redirectUrl', $content);

        $resultData = json_decode($content, true);
        self::assertArrayHasKey('redirectUrl', $resultData);

        $flashMessages = $this->getSession()->getFlashBag()->all();
        self::assertEquals(['success' => [0 => 'Product has been duplicated']], $flashMessages);
    }

    /**
     * @param Product $product
     * @param string $operationName
     */
    protected function executeOperation(Product $product, string $operationName): void
    {
        $entityId = $product->getId();
        $entityClass = Product::class;
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_product_view',
                    'entityId' => $entityId,
                    'entityClass' => $entityClass
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
