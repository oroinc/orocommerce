<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadPageMetaData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolation
 */
class PageControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData']);
    }

    public function testViewLandingPage()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_view', ['id' => $this->getPageId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    public function testEditLandingPage()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $this->getPageId()]));

        $this->checkSeoSectionExistence($crawler);

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('category');
        $parameters = [
            'input_action' => 'save_and_stay',
            'oro_catalog_category' => ['_token' => $crfToken],
        ];
        $parameters['oro_cms_page']['metaDescriptions']['values']['default'] = LoadPageMetaData::META_DESCRIPTIONS;
        $parameters['oro_cms_page']['metaKeywords']['values']['default'] = LoadPageMetaData::META_KEYWORDS;

        $form = $crawler->selectButton('Save')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(LoadPageMetaData::META_DESCRIPTIONS, $html);
        $this->assertContains(LoadPageMetaData::META_KEYWORDS, $html);
    }

    /**
     * @return int|null
     */
    protected function getPageId()
    {
        $class = $this->getContainer()->getParameter('oro_cms.entity.page.class');
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass($class)->getRepository($class);
        $qb = $repository->createQueryBuilder('page');

        return $qb
            ->select('page.id')
            ->innerJoin('page.slugs', 'slug')
            ->andWhere('slug.string = :slug')
            ->setParameter('slug', 'about')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Crawler $crawler
     */
    public function checkSeoSectionExistence(Crawler $crawler)
    {
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('SEO', $crawler->filter('.nav')->html());
        $this->assertContains('Meta description', $crawler->html());
        $this->assertContains('Meta keywords', $crawler->html());
    }
}
