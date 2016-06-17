<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData;

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
            'OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData',
            'OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData',
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
            $this->getUrl('orob2b_cms_frontend_page_view', ['id' => $page->getId()])
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
        $title1 = $this->getMetaContent(LoadPageData::PAGE_1, LoadPageMetaData::META_TITLES);
        $description1 = $this->getMetaContent(LoadPageData::PAGE_1, LoadPageMetaData::META_DESCRIPTIONS);
        $keywords1 = $this->getMetaContent(LoadPageData::PAGE_1, LoadPageMetaData::META_KEYWORDS);

        return [
            'Product 1' => [
                'product' => LoadPageData::PAGE_1,
                'metaTags' => [
                    ['name' => $this->getMetaTitleName(), 'content' => $title1],
                    ['name' => $this->getMetaDescriptionName(), 'content' => $description1],
                    ['name' => $this->getMetaKeywordsName(), 'content' => $keywords1],
                ],
            ],
            'Product 2' => [
                'product' => LoadPageData::PAGE_1_2,
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
     * @param string $entity
     * @return array
     */
    protected function getMetadataArray($entity)
    {
        return LoadPageMetaData::$metadata;
    }
}
