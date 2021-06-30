<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FrontendControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadCategoryProductData::class,
            LoadProductData::class
        ]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertNotEmpty($content);
        static::assertStringContainsString('list-slider-component', $content);
        static::assertStringContainsString('Featured Products', $content);
        static::assertStringContainsString('Top Selling Items', $content);
    }
}
