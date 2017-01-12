<?php

namespace Oro\Bundle\CatalogBundle\Bundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
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
