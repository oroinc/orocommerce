<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadDraftPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadDraftPageSlugData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Event\RestrictSlugIncrementEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DraftRestrictSlugIncrementListenerTest extends WebTestCase
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadDraftPageSlugData::class
            ]
        );

        $this->eventDispatcher = $this->getContainer()->get('event_dispatcher');
    }

    public function testOnRestrictSlugIncrementEventForDraft(): void
    {
        /** @var Page $page */
        $entity = $this->getReferenceRepository()->getReference(LoadDraftPageData::BASIC_PAGE_1_DRAFT_1);

        $queryBuilder = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder('/basic-page-1-url', $entity);
        $this->assertNotNull($queryBuilder->getQuery()->getOneOrNullResult());

        $event = new RestrictSlugIncrementEvent($queryBuilder, $entity);
        $this->eventDispatcher->dispatch(RestrictSlugIncrementEvent::NAME, $event);

        $this->assertNull($queryBuilder->getQuery()->getOneOrNullResult());
    }

    public function testOnRestrictSlugIncrementEventForNonDraft(): void
    {
        /** @var Page $page */
        $entity = $this->getReferenceRepository()->getReference(LoadDraftPageData::BASIC_PAGE_2);

        $queryBuilder = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder('/draft-2-url', $entity);
        $this->assertNotNull($queryBuilder->getQuery()->getOneOrNullResult());

        $event = new RestrictSlugIncrementEvent($queryBuilder, $entity);
        $this->eventDispatcher->dispatch(RestrictSlugIncrementEvent::NAME, $event);

        $this->assertNull($queryBuilder->getQuery()->getOneOrNullResult());
    }

    /**
     * @return SlugRepository
     */
    private function getSlugRepository(): SlugRepository
    {
        return $this->getContainer()->get('doctrine')->getManager()->getRepository(Slug::class);
    }
}
