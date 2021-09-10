<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadProductMetaData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\DomCrawler\Crawler;

class ProductControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);
    }

    public function testViewProduct()
    {
        $product = $this->getReference('product-1');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    public function testEditProduct()
    {
        $product = $this->getReference('product-1');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);

        $form = $crawler->selectButton('Save')->form();
        $parameters = ArrayUtil::arrayMergeRecursiveDistinct(
            $form->getPhpValues(),
            [
                'input_action' => 'save_and_stay',
                'oro_product_product' => [
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

        static::assertStringContainsString(LoadProductMetaData::META_TITLES, $html);
        static::assertStringContainsString(LoadProductMetaData::META_DESCRIPTIONS, $html);
        static::assertStringContainsString(LoadProductMetaData::META_KEYWORDS, $html);
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
