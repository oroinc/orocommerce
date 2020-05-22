<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductActionsTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

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
        static::assertStringContainsString('redirectUrl', $content);

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
        $entityId = $product->getId();
        $entityClass = 'Oro\Bundle\ProductBundle\Entity\Product';
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

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass
        ];
        $container = static::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }
}
