<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadCategoryMetaData;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testEditCategory()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('oro_catalog.entity.category.class')
        );

        $category = $repository->findOneBy([]);

        $id = $category->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', ['id' => $id]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'oro_catalog_category' => ['_token' => $crfToken],
        ];
        $parameters['oro_catalog_category']['metaTitles']['values']['default'] = LoadCategoryMetaData::META_TITLES;
        $parameters['oro_catalog_category']['metaDescriptions']['values']['default'] =
            LoadCategoryMetaData::META_DESCRIPTIONS;
        $parameters['oro_catalog_category']['metaKeywords']['values']['default'] =
            LoadCategoryMetaData::META_KEYWORDS;

        $form = $crawler->selectButton('Save')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(LoadCategoryMetaData::META_TITLES, $html);
        $this->assertContains(LoadCategoryMetaData::META_DESCRIPTIONS, $html);
        $this->assertContains(LoadCategoryMetaData::META_KEYWORDS, $html);
    }

    public function checkSeoSectionExistence($crawler)
    {
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('SEO', $crawler->filter('.nav')->html());
        $this->assertContains('Meta title', $crawler->html());
        $this->assertContains('Meta description', $crawler->html());
        $this->assertContains('Meta keywords', $crawler->html());
    }
}
