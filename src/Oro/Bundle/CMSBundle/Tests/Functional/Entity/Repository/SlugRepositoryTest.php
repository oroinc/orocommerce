<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageSlugData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Helper\SlugScopeHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

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

    public function testDeleteSlugAttachedToEntityByClass(): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $this->assertNotEmpty($page->getSlugs());

        $this->repository->deleteSlugAttachedToEntityByClass(Page::class);

        $em = $this->registry->getManagerForClass(Page::class);
        $em->refresh($page);

        $this->assertEmpty($page->getSlugs());
    }

    public function testGetExistingOrphanSlug(): void
    {
        $expectedSlug = $this->getReference(LoadPageSlugData::SLUG1_PAGE1);
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $urlHash = md5($expectedSlug->getUrl());
        $scopeHash = SlugScopeHelper::getScopesHash(new ArrayCollection(), null);

        $slug = $this->repository->getSlugByOrganizationAndHashes($organization->getId(), $urlHash, $scopeHash);

        self::assertEquals($expectedSlug, $slug);
    }

    public function testGetNotExistingOrphanSlug(): void
    {
        $urlHash = md5('');
        $scopeHash = SlugScopeHelper::getScopesHash(new ArrayCollection(), null);

        $slug = $this->repository->getSlugByOrganizationAndHashes(0, $urlHash, $scopeHash);

        self::assertEquals(null, $slug);
    }
}
