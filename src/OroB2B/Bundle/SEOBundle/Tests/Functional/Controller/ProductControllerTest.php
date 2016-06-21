<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ArrayUtil;

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
        $product = $this->getReference('product.1');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    public function testEditProduct()
    {
        $product = $this->getReference('product.1');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);

        $form = $crawler->selectButton('Save')->form();
        $parameters = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'input_action' => 'save_and_stay',
                'orob2b_product_product' => [
                    'metaTitles' => [
                        'values' => [
                            'default' => LoadProductMetaData::META_TITLES
                        ]
                    ],
                    'metaDescriptions' => [
                        'values' => [
                            'default' => LoadProductMetaData::META_DESCRIPTIONS
                        ]
                    ],
                    'metaKeywords' => [
                        'values' => [
                            'default' => LoadProductMetaData::META_KEYWORDS
                        ]
                    ]
                ]
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(LoadProductMetaData::META_TITLES, $html);
        $this->assertContains(LoadProductMetaData::META_DESCRIPTIONS, $html);
        $this->assertContains(LoadProductMetaData::META_KEYWORDS, $html);
    }

    /**
     * @param Crawler $crawler
     */
    public function checkSeoSectionExistence(Crawler $crawler)
    {
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('SEO', $crawler->filter('.nav')->html());
        $this->assertContains('Meta title', $crawler->html());
        $this->assertContains('Meta description', $crawler->html());
        $this->assertContains('Meta keywords', $crawler->html());
    }
}
