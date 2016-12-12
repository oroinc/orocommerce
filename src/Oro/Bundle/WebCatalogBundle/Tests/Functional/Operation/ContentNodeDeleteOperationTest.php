<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;

/**
 * @dbIsolation
 */
class ContentNodeDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['\Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData']);
    }

    public function testDelete()
    {
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);

        $this->client->followRedirects();

        $this->assertExecuteOperation(
            'DELETE',
            $contentNode->getId(),
            $this->getContainer()->getParameter('oro_web_catalog.entity.content_node.class'),
            ['datagrid' => 'web-catalog-grid']
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'refreshGrid' => [
                    'web-catalog-grid'
                ],
                'flashMessages' => [
                    'success' => ['Content Node deleted']
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
