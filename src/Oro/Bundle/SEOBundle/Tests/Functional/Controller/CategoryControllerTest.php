<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadCategoryMetaData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CategoryControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testEditCategory()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(Category::class);

        $category = $repository->findOneBy([]);

        $id = $category->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_catalog_category_update', ['id' => $id]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getCsrfToken('category')->getValue();
        $parameters = [
            'input_action' => 'save_and_stay',
            'oro_catalog_category' => ['_token' => $crfToken],
        ];

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
        static::assertStringNotContainsString('The CSRF token is invalid. Please try to resubmit the form.', $html);

        static::assertStringContainsString(LoadCategoryMetaData::META_DESCRIPTIONS, $html);
        static::assertStringContainsString(LoadCategoryMetaData::META_KEYWORDS, $html);
    }

    public function checkSeoSectionExistence(Crawler $crawler)
    {
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('SEO', $crawler->filter('.nav')->html());
        static::assertStringContainsString('Meta title', $crawler->html());
        static::assertStringContainsString('Meta description', $crawler->html());
        static::assertStringContainsString('Meta keywords', $crawler->html());
    }
}
