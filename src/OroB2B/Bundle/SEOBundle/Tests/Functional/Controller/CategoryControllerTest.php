<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadCategoryMetaData;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testEditCategory()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_catalog.entity.category.class')
        );

        $category = $repository->findOneBy([]);

        $id = $category->getId();
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_catalog_category' => ['_token' => $crfToken],
        ];
        $parameters['orob2b_catalog_category']['metaTitles']['values']['default'] = LoadCategoryMetaData::META_TITLES;
        $parameters['orob2b_catalog_category']['metaDescriptions']['values']['default'] =
            LoadCategoryMetaData::META_DESCRIPTIONS;
        $parameters['orob2b_catalog_category']['metaKeywords']['values']['default'] =
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
