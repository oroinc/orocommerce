<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData;

/**
 * @dbIsolation
 */
class PageControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData']);
    }

    public function testViewLandingPage()
    {
        $page = $this->getPage();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_page_view', ['id' => $page->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    public function testEditLandingPage()
    {
        $page = $this->getPage();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_page_update', ['id' => $page->getId()]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_catalog_category' => ['_token' => $crfToken],
        ];
        $parameters['orob2b_cms_page']['metaTitles']['values']['default'] = LoadPageMetaData::META_TITLES;
        $parameters['orob2b_cms_page']['metaDescriptions']['values']['default'] = LoadPageMetaData::META_DESCRIPTIONS;
        $parameters['orob2b_cms_page']['metaKeywords']['values']['default'] = LoadPageMetaData::META_KEYWORDS;

        $form = $crawler->selectButton('Save')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(LoadPageMetaData::META_TITLES, $html);
        $this->assertContains(LoadPageMetaData::META_DESCRIPTIONS, $html);
        $this->assertContains(LoadPageMetaData::META_KEYWORDS, $html);
    }

    protected function getPage()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_cms.entity.page.class')
        );

        return $repository->findOneBy(['title' => 'page.1']);
    }

    public function checkSeoSectionExistence($crawler)
    {
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('SEO', $crawler->filter('.nav')->html());
        $this->assertContains('Meta title', $crawler->html());
        $this->assertContains('Meta description', $crawler->html());
        $this->assertContains('Meta keywords', $crawler->html());
    }
}
