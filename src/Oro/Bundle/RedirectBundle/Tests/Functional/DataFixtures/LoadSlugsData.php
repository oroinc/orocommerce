<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class LoadSlugsData extends AbstractFixture implements DependentFixtureInterface
{
    const SLUG_URL_ANONYMOUS = '/slug/anonymous';
    const SLUG_URL_USER = '/slug/customer';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $this->createSlug($manager, self::SLUG_URL_ANONYMOUS, 'oro_cms_frontend_page_view', ['id' => $page->getId()]);
        $this->createSlug($manager, self::SLUG_URL_USER, 'oro_customer_frontend_account_user_index', []);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $url
     * @param string $routeName
     * @param array $routeParameters
     * @return Slug
     */
    protected function createSlug(ObjectManager $manager, $url, $routeName, array $routeParameters)
    {
        $slug = new Slug();
        $slug->setUrl($url);
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);

        $manager->persist($slug);
        $this->addReference($url, $slug);

        return $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadPageData::class];
    }
}
