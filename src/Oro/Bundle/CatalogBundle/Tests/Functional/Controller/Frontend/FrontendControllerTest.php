<?php

namespace Oro\Bundle\CatalogBundle\Bundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures([
            'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
        ]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertNotEmpty($content);
        $this->assertContains(LoadCategoryData::FIRST_LEVEL, $content);
        $this->assertContains(LoadCategoryData::SECOND_LEVEL1, $content);
        $this->assertContains(LoadCategoryData::SECOND_LEVEL2, $content);
        $this->assertContains(LoadCategoryData::THIRD_LEVEL1, $content);
        $this->assertContains(LoadCategoryData::THIRD_LEVEL2, $content);
        $this->assertContains(LoadCategoryData::FOURTH_LEVEL1, $content);
        $this->assertContains(LoadCategoryData::FOURTH_LEVEL2, $content);
    }
}
