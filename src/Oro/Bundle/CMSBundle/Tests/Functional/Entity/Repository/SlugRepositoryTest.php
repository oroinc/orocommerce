<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageSlugData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SlugRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SlugRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPageSlugData::class]);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(Slug::class);
    }

    public function testDeleteSlugAttachedToEntityByClass()
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $this->assertNotEmpty($page->getSlugs());

        $this->repository->deleteSlugAttachedToEntityByClass(Page::class);

        $em = $this->registry->getManagerForClass(Page::class);
        $em->refresh($page);

        $this->assertEmpty($page->getSlugs());
    }
}
