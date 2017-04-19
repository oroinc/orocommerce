<?php

namespace Oro\Bundle\CatalogBundle\Bundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FrontendControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadCategoryProductData::class,
            LoadProductImageData::class
        ]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertNotEmpty($content);
        $this->assertContains('list-slider-component', $content);
        $this->assertContains('Featured Products', $content);
        $this->assertContains('Top Selling Items', $content);
    }
}
