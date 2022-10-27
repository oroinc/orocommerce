<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;

class ContentNodeDeleteOperationTest extends ActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadContentNodesData::class]);
    }

    public function testDelete()
    {
        $contentNodeId = $this->getIdByReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $subNodeOneId = $this->getIdByReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $subNodeTwoId = $this->getIdByReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $nodesIdsBeforeRemove = $this->getNodeIds();

        $this->client->followRedirects();

        $this->assertExecuteOperation(
            'DELETE',
            $contentNodeId,
            ContentNode::class,
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
                ],
                'pageReload' => true
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        // Check that removed only correct nodes.
        $nodeIdsAfterRemove = $this->getNodeIds();
        $removedNodeIds = array_diff($nodesIdsBeforeRemove, $nodeIdsAfterRemove);
        $this->assertCount(3, $removedNodeIds);
        $this->assertContains($contentNodeId, $removedNodeIds);
        $this->assertContains($subNodeOneId, $removedNodeIds);
        $this->assertContains($subNodeTwoId, $removedNodeIds);
    }

    /**
     * @param string $reference
     * @return int
     */
    private function getIdByReference($reference)
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference($reference);

        return $contentNode->getId();
    }

    /**
     * @return array
     */
    private function getNodeIds()
    {
        /** @var ContentNodeRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
        $nodeIds = array_map(
            function (ContentNode $contentNode) {
                return $contentNode->getId();
            },
            $repository->findAll()
        );

        return $nodeIds;
    }
}
