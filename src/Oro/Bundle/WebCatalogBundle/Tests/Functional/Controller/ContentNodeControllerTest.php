<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;

class ContentNodeControllerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadContentVariantsData::class,
            LoadPageData::class,
        ]);
    }

    public function testGetPossibleUrlsAction(): void
    {
        /** @var ContentNode $firstCatalogNode */
        $firstCatalogNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $slugGenerator = self::getContainer()->get('oro_web_catalog.generator.slug_generator');
        $slugGenerator->generate($firstCatalogNode);
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(ContentNode::class);
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
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $expected = [
            'Default Value' => [
                'before' => '/web_catalog.node.1.1/web_catalog.node.1.1.1',
                'after' => '/web_catalog.node.1.2/web_catalog.node.1.1.1'
            ]
        ];
        $actual = json_decode($result->getContent(), true);
        self::assertEquals($expected, $actual);
    }

    public function testRedirectCreationDuringMove(): void
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        /** @var ContentNode $newParentContentNode */
        $newParentContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $this->ajaxRequest(
            'PUT',
            $this->getUrl(
                'oro_content_node_move',
                [
                    'id' => $contentNode->getId(),
                    'parent' => $newParentContentNode->getId(),
                    'position' => 0,
                    'createRedirect' => 1
                ]
            )
        );

        $expectedMessage = [
            'topic' => WebCatalogResolveContentNodeSlugsTopic::getName(),
            'message' => [
                WebCatalogResolveContentNodeSlugsTopic::ID => $contentNode->getId(),
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true
            ]
        ];

        self::assertContains($expectedMessage, self::getSentMessages());
    }

    public function testValidationForLocalizedFallbackValues(): void
    {
        $rootNodeId = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_content_node_create', ['id' => $rootNodeId]));
        $form = $crawler->selectButton('Save')->form();
        $redirectAction = $crawler->selectButton('Save')->attr('data-action');

        $bigStringValue = str_repeat('a', 256);
        $formValues = $form->getPhpValues();
        $formValues['oro_web_catalog_content_node']['parentNode'] = $rootNodeId;
        $formValues['oro_web_catalog_content_node']['titles']['values']['default'] = $bigStringValue;
        $formValues['oro_web_catalog_content_node']['slugPrototypesWithRedirect']['slugPrototypes'] = [
            'values' => ['default' => $bigStringValue]
        ];
        $formValues['oro_web_catalog_content_node']['contentVariants'][] = [
            'default' => 1,
            'cmsPage' => $this->getReference(LoadPageData::PAGE_1)->getId(),
            'type' => 'cms_page',
        ];
        $formValues['input_action'] = $redirectAction;

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertEquals(
            2,
            $crawler->filterXPath(
                "//li[contains(text(),'This value is too long. It should have 255 characters or less.')]"
            )->count()
        );
    }

    public function testGetChangedUrlsWhenSlugChanged(): void
    {
        $localization = self::getContainer()->get('oro_locale.manager.localization')->getDefaultLocalization(false);

        /** @var ContentNode $contentNodeReference */
        $contentNodeReference = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(ContentNode::class);
        $contentNode = $entityManager->find(ContentNode::class, $contentNodeReference->getId());

        $contentNode->setDefaultSlugPrototype('old-default-slug');
        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('old-english-slug')->setLocalization($localization);

        $contentNode->addSlugPrototype($slugPrototype);
        $entityManager->flush($contentNode);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_content_node_update', ['id' => $contentNode->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();

        $formValues['oro_web_catalog_content_node']['slugPrototypesWithRedirect'] = [
            'slugPrototypes' => [
                'values' => [
                    'default' => 'default-slug',
                    'localizations' => [
                        $localization->getId() => ['value' => 'english-slug']
                    ]
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_content_node_get_changed_urls', ['id' => $contentNode->getId()]),
            $formValues
        );

        $expectedData = [
            'Default Value' => ['before' => '/old-default-slug', 'after' => '/default-slug'],
            'English (United States)' => ['before' => '/old-english-slug', 'after' => '/english-slug']
        ];

        $response = $this->client->getResponse();
        self::assertJsonStringEqualsJsonString(json_encode($expectedData), $response->getContent());
    }
}
