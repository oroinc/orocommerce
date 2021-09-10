<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class BrandControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadBrandData::class]);
    }

    public function testIndexAction(): void
    {
        $this->client->request('GET', $this->getUrl('oro_product_frontend_brand_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testViewAction(): void
    {
        $brandId = $this->getReference(LoadBrandData::BRAND_1)->getId();
        $this->client->request('GET', $this->getUrl('oro_product_frontend_brand_view', ['id' => $brandId]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}
