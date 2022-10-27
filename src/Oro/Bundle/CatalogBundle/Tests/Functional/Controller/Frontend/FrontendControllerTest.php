<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class FrontendControllerTest extends WebTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadCategoryProductData::class,
            LoadProductData::class,
        ]);

        self::reindex(Product::class);
    }

    public function testIndex(): void
    {
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        self::assertNotEmpty($content);
        self::assertStringContainsString('list-slider-component', $content);
        self::assertStringContainsString('Featured Products', $content);
        self::assertStringContainsString('Top Selling Items', $content);
    }
}
