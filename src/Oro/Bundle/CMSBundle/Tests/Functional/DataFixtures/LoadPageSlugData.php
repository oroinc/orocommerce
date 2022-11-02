<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class LoadPageSlugData extends AbstractFixture implements DependentFixtureInterface
{
    const SLUG1_PAGE1 = 'orocrm.page1.slug1';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadPageData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $slug = new Slug();
        $slug->setRouteName('oro_cms_frontend_page_view');
        $slug->setRouteParameters(['id' => $page->getId()]);
        $slug->setUrl('/page1-slug-url');

        $page->addSlug($slug);
        $manager->flush();

        $this->addReference(self::SLUG1_PAGE1, $slug);
    }
}
