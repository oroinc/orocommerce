<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Functinal\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\RedirectBundle\Entity\Slug;

/**
 * @dbIsolation
 */
class PageSlugListenerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();
        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BCMSBundle:Page');
    }

    public function testPersistPageSlug()
    {
        $page = new Page();
        $page->setTitle('Test')
            ->setContent('<p>test</p>')
            ->setCurrentSlugUrl('/persist');

        $this->entityManager->persist($page);
        $this->entityManager->flush($page);

        $pageId = $page->getId();
        $currentSlug = $page->getCurrentSlug();

        $expectedRouteName = 'orob2b_cms_page_view';
        $expectedRouteParameters = ['id' => $pageId];

        $this->assertEquals($expectedRouteName, $currentSlug->getRouteName());
        $this->assertEquals($expectedRouteParameters, $currentSlug->getRouteParameters());

        // make sure data persisted correctly
        $this->entityManager->clear();

        $persistedPage = $this->entityManager->find('OroB2BCMSBundle:Page', $pageId);
        $persistedPageSlug = $persistedPage->getCurrentSlug();

        $this->assertEquals($expectedRouteName, $persistedPageSlug->getRouteName());
        $this->assertEquals($expectedRouteParameters, $persistedPageSlug->getRouteParameters());

        return $pageId;
    }

    /**
     * @param int $pageId
     * @depends testPersistPageSlug
     */
    public function testUpdatePageSlug($pageId)
    {
        $page = $this->entityManager->find('OroB2BCMSBundle:Page', $pageId);

        $currentSlug = $page->getCurrentSlug();
        $currentSlug->setRouteName('incorrect_route')
            ->setRouteParameters(['invalid' => 'parameters']);

        $newSlug = new Slug();
        $newSlug->setUrl('/update')
            ->setRouteName('incorrect_route')
            ->setRouteParameters(['invalid' => 'parameters']);

        $page->setCurrentSlug($newSlug);

        $this->entityManager->flush($page);

        $expectedRouteName = 'orob2b_cms_page_view';
        $expectedRouteParameters = ['id' => $pageId];

        foreach ($page->getSlugs() as $slug) {
            $this->assertEquals($expectedRouteName, $slug->getRouteName());
            $this->assertEquals($expectedRouteParameters, $slug->getRouteParameters());
        }

        // make sure data updated correctly
        $this->entityManager->clear();

        $updatedPage = $this->entityManager->find('OroB2BCMSBundle:Page', $pageId);

        foreach ($updatedPage->getSlugs() as $slug) {
            $this->assertEquals($expectedRouteName, $slug->getRouteName());
            $this->assertEquals($expectedRouteParameters, $slug->getRouteParameters());
        }
    }
}
