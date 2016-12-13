<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData;

/**
 * @dbIsolation
 */
class PageControllerTest extends WebTestCase
{
    use SEOFrontendTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData',
            'Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData',
        ]);
    }

    /**
     * @dataProvider viewDataProvider
     * @param string $page
     * @param array $metaTags
     */
    public function testView($page, array $metaTags)
    {
        $page = $this->getPage($page);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_frontend_page_view', ['id' => $page->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->checkSEOFrontendMetaTags($crawler, $metaTags);
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        $description1 = $this->getMetaContent(LoadPageData::PAGE_1, LoadPageMetaData::META_DESCRIPTIONS);
        $keywords1 = $this->getMetaContent(LoadPageData::PAGE_1, LoadPageMetaData::META_KEYWORDS);

        return [
            'Product 1' => [
                'product' => LoadPageData::PAGE_1,
                'metaTags' => [
                    ['name' => $this->getMetaDescriptionName(), 'content' => $description1],
                    ['name' => $this->getMetaKeywordsName(), 'content' => $keywords1],
                ],
            ],
            'Product 2' => [
                'product' => LoadPageData::PAGE_2,
                'metaTags' => [
                    ['name' => 'title', 'content' => ''],
                    ['name' => 'description', 'content' => ''],
                    ['name' => 'keywords', 'content' => ''],
                ],
            ],
        ];
    }

    /**
     * @param string $reference
     * @return Page
     */
    protected function getPage($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return array
     */
    protected function getMetadataArray()
    {
        return LoadPageMetaData::$metadata;
    }
}
