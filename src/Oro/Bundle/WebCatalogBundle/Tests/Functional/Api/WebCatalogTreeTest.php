<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class WebCatalogTreeTest extends RestJsonApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadContentNodesData::class,
        ]);
    }

    public function testGet()
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $response = $this->get(
            ['entity' => 'webcatalogs', 'id' => $webCatalog->getId()]
        );

        $jsonContent = json_decode($response->getContent(), true);
        $expectedContent = $this->getExpectedWebCatalogTree();

        $this->assertEquals($expectedContent, $jsonContent['data']['attributes']['tree']);
    }

    /**
     * @return array
     */
    public function getExpectedWebCatalogTree()
    {
        $contentNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $contentNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $contentNode3 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $contentNode4 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $contentNode5 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);
        return [
            [
                'id' => $contentNode1->getId(),
                'parent' => '#',
                'text' => LoadContentNodesData::CATALOG_1_ROOT,
                'state' => ['opened' => true]
            ],
            [
                'id' => $contentNode2->getId(),
                'parent' => $contentNode2->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode3->getId(),
                'parent' => $contentNode3->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode4->getId(),
                'parent' => $contentNode4->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode5->getId(),
                'parent' => $contentNode5->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2,
                'state' => ['opened' => false]
            ],
        ];
    }
}
