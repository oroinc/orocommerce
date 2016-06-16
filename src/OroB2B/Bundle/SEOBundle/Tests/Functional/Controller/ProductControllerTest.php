<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadProductMetaData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);
    }

    public function testViewProduct()
    {
        $product = $this->getProduct();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    public function testEditProduct()
    {
        $product = $this->getProduct();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_catalog_category' => ['_token' => $crfToken],
        ];
        $parameters['orob2b_product_product']['metaTitles']['values']['default'] = LoadProductMetaData::META_TITLES;
        $parameters['orob2b_product_product']['metaDescriptions']['values']['default'] =
            LoadProductMetaData::META_DESCRIPTIONS;
        $parameters['orob2b_product_product']['metaKeywords']['values']['default'] = LoadProductMetaData::META_KEYWORDS;

        $form = $crawler->selectButton('Save')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(LoadProductMetaData::META_TITLES, $html);
        $this->assertContains(LoadProductMetaData::META_DESCRIPTIONS, $html);
        $this->assertContains(LoadProductMetaData::META_KEYWORDS, $html);
    }

    protected function getProduct()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.entity.product.class')
        );

        return $repository->findOneBy(['sku' => 'product.1']);
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
