<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

/**
 * @group regression
 */
class AddToCartSubresourceDocumentationTest extends FrontendRestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'frontend_rest_json_api';

    public function testDocumentation(): void
    {
        $this->warmUpDocumentationCache();
        $docs = $this->getEntityDocsForAction('shoppinglists', ApiAction::ADD_SUBRESOURCE);

        $resourceData = $this->getSubresourceData(
            $this->getSimpleFormatter()->format($docs),
            'shoppinglists/{id}/items'
        );
        self::assertStringStartsWith('Add or Update Items', $resourceData['description']);
        self::assertStringContainsString('Add an item or the list of items', $resourceData['documentation']);
        $expectedData = $this->loadYamlData('add_to_cart_subresource.yml', 'documentation');
        self::assertArrayContains($expectedData, $resourceData);
    }
}
