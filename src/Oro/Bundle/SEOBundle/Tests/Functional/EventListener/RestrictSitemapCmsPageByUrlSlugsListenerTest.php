<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageSlugData;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCmsPageByUrlSlugsListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestrictSitemapCmsPageByUrlSlugsListenerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPageSlugData::class,
        ]);
    }

    public function testRestrictQueryBuilderWhenSlugsNotJoined(): void
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getRepository(Page::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS . '.id');

        $listener = new RestrictSitemapCmsPageByUrlSlugsListener();
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($qb, time()));

        $actualIds = array_map('current', $qb->getQuery()->getResult());
        sort($actualIds);

        $expected = [
            LoadPageData::PAGE_1,
        ];

        $expectedIds = array_map(
            fn (string $referenceName): int => $this->getReference($referenceName)->getId(),
            $expected
        );
        sort($expectedIds);

        self::assertEquals($expectedIds, $actualIds);
    }

    public function testRestrictQueryBuilderWhenSlugsAlreadyJoined(): void
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getRepository(Page::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS . '.id')
            ->leftJoin(UrlItemsProvider::ENTITY_ALIAS . '.slugs', 'slugs')
            ->addSelect('slugs.url');

        $listener = new RestrictSitemapCmsPageByUrlSlugsListener();
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($qb, time()));

        $actualIds = array_unique(array_map('current', $qb->getQuery()->getResult()));
        sort($actualIds);

        $expected = [
            LoadPageData::PAGE_1,
        ];

        $expectedIds = array_map(
            fn (string $referenceName): int => $this->getReference($referenceName)->getId(),
            $expected
        );
        sort($expectedIds);

        self::assertEquals($expectedIds, array_values($actualIds));
    }
}
