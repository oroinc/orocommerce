<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ExceptionControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);
    }

    /**
     * @dataProvider showActionNotFoundDataProvider
     * @param string $url
     * @param string $selector
     */
    public function testShowActionNotFound($url, $selector)
    {
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
        $this->assertContains('404 Not Found', $crawler->filter($selector)->text());
    }

    /**
     * @return array
     */
    public function showActionNotFoundDataProvider()
    {
        return [
            [
                'url' => '/page-does-not-exist',
                'selector' => 'div.text-center h1',
            ],
            [
                'url' => '/admin/page-does-not-exist',
                'selector' => 'div.popup-frame div.popup-holder div.pagination-centered.popup-box-errors h1',
            ],
        ];
    }

    public function testShowActionUnauthorized()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_4);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $expectedCode = 403;
        $this->assertHtmlResponseStatusCodeEquals($result, $expectedCode);
        $this->assertContains((string)$expectedCode, $crawler->html());
        $this->assertContains('Forbidden', $crawler->html());
    }
}
