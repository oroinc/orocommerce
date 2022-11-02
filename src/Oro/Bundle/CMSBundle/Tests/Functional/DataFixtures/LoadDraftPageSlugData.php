<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class LoadDraftPageSlugData extends AbstractFixture implements DependentFixtureInterface
{
    public const BASIC_PAGE_1_SLUG = 'basic.page1.slug';
    public const BASIC_PAGE_1_DRAFT_1_SLUG = 'basic.page.1.draft.1.slug';
    public const BASIC_PAGE_1_DRAFT_2_SLUG = 'basic.page.1.draft.2.slug';
    public const BASIC_PAGE_2_SLUG = 'basic.page.2.slug';

    /**
     * @var array
     */
    private static $slugs = [
        self::BASIC_PAGE_1_SLUG => [
            'page' => LoadDraftPageData::BASIC_PAGE_1,
            'url' => '/basic-page-1-url'
        ],
        self::BASIC_PAGE_1_DRAFT_1_SLUG => [
            'page' => LoadDraftPageData::BASIC_PAGE_1_DRAFT_1,
            'url' => '/basic-page-1-url'
        ],
        self::BASIC_PAGE_1_DRAFT_2_SLUG => [
            'page' => LoadDraftPageData::BASIC_PAGE_1_DRAFT_2,
            'url' => '/draft-2-url'
        ],
        self::BASIC_PAGE_2_SLUG => [
            'page' => LoadDraftPageData::BASIC_PAGE_2,
            'url' => '/draft-2-url'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadDraftPageData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var FilterCollection $filters */
        $filters = $manager->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
        }

        foreach (self::$slugs as $reference => $data) {
            /** @var Page $page */
            $page = $this->getReference($data['page']);

            $slug = new Slug();
            $slug->setRouteName('oro_cms_frontend_page_view');
            $slug->setRouteParameters(['id' => $page->getId()]);
            $slug->setUrl($data['url']);

            $page->addSlug($slug);

            $this->setReference($reference, $slug);
            $manager->persist($slug);
        }

        $manager->flush();

        $filters->enable(DraftableFilter::FILTER_ID);
    }
}
