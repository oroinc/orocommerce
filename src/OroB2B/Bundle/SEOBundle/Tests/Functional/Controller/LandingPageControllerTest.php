<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LandingPageControllerTest extends WebTestCase
{
    use SEOViewSectionTrait;
    
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
    }

    protected function getPage()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_cms.entity.page.class')
        );

        return $repository->findOneBy(['title' => 'page.1']);
    }
}
