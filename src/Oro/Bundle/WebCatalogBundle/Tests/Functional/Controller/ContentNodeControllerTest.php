<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;

/**
 * @dbIsolation
 */
class ContentNodeControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadContentVariantsData::class]);
    }

    public function testGetPossibleUrlsAction()
    {
        /** @var ContentNode $firstCatalogNode */
        $firstCatalogNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $slugGenerator = $this->getContainer()->get('oro_web_catalog.generator.slug_generator');
        $slugGenerator->generate($firstCatalogNode);
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ContentNode::class);
        $em->flush();

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        /** @var ContentNode $newParentContentNode */
        $newParentContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_content_node_get_possible_urls',
                ['id' => $contentNode->getId(), 'newParentId' => $newParentContentNode->getId()]
            )
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $expected = [
            'Default Value' => [
                'before' => '/web_catalog.node.1.1/web_catalog.node.1.1.1',
                'after' => '/web_catalog.node.1.2/web_catalog.node.1.1.1'
            ]
        ];
        $actual = json_decode($result->getContent(), true);
        $this->assertEquals($expected, $actual);
    }
}
