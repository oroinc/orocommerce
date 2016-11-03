<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;

/**
 * @dbIsolation
 */
class ContentNodeControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                '\Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData',
            ]
        );
    }

    public function testDelete()
    {
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_content_node', ['id' => $contentNode->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $removedChildNodes = [
            LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
            LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2
        ];

        /** @var ContentNodeRepository $em */
        $repo = $this->getContainer()->get('doctrine')->getRepository('OroWebCatalogBundle:ContentNode');

        foreach ($removedChildNodes as $removedChildNode) {
            $this->assertEmpty($repo->findOneByName($removedChildNode));
        }
    }
}
