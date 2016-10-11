<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CMSBundle\Entity\Page;

/**
 * @dbIsolation
 */
class PageControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
    }

    public function testViewBySlug()
    {
        $registry = $this->getContainer()->get('doctrine');
        $pageEntityManager = $registry->getManagerForClass('OroCMSBundle:Page');

        $organization = $registry->getManagerForClass('OroOrganizationBundle:Organization')
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();

        $slug = '/test-slug';
        $title = 'Test Page';
        $content = '<p>Test content</p>';

        $page = new Page();
        $page->setCurrentSlugUrl($slug)
            ->setTitle($title)
            ->setContent($content)
            ->setOrganization($organization);

        $pageEntityManager->persist($page);
        $pageEntityManager->flush();

        $crawler = $this->client->request('GET', $slug);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $pageHtml = $crawler->html();
        $this->assertContains($title, $pageHtml);
        $this->assertContains($content, $pageHtml);
    }
}
