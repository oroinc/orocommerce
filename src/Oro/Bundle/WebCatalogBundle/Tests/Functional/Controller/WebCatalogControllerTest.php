<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadWebCatalogData::class,
            LoadContentNodesData::class,
        ]);
    }

    public function testMove()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_web_catalog_move',
                ['id' => $this->getReference(LoadWebCatalogData::CATALOG_1)->getId()]
            ),
            [
                'selected' => [
                    $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1)->getId()
                ],
                '_widgetContainer' => 'dialog',
            ],
            [],
            $this->generateWsseAuthHeader()
        );

        $form = $crawler->selectButton('Save')->form();
        $values = $form->getValues();

        $this->assertEquals(
            [$this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1)->getId()],
            $values['tree_move[source]']
        );

        $form['tree_move[source]'] = [$this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2)->getId()];
        $form['tree_move[target]'] = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT)->getId();

        $this->client->followRedirects(true);

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '?_widgetContainer=dialog'
        );

        $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var ContentNodeRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroWebCatalogBundle:ContentNode')
            ->getRepository('OroWebCatalogBundle:ContentNode');
        $node = $repository->find($this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2)->getId());
        $this->assertEquals($node->getParentNode()->getTitle(), LoadContentNodesData::CATALOG_1_ROOT);
    }
}
