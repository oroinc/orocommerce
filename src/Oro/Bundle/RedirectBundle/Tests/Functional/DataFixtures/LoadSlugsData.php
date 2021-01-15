<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class LoadSlugsData extends AbstractFixture implements DependentFixtureInterface
{
    const SLUG_URL_ANONYMOUS = '/slug/anonymous';
    const SLUG_URL_USER = '/slug/customer';
    const SLUG_URL_PAGE = '/slug/page';
    const SLUG_URL_PAGE_2 = '/slug/page2';
    const SLUG_URL_LOCALIZATION_1 = '/slug/en/page';
    const SLUG_URL_LOCALIZATION_2 = '/slug/es/page';
    const SLUG_TEST_DUPLICATE_URL = '/slug/first';
    const SLUG_TEST_DUPLICATE_REFERENCE = 'reference:/slug/first';
    const SLUG_TEST_ONLY = '__test-only__';
    const PAGE_3_DEFAULT = '/localized-slug/en/page3';
    const PAGE_3_LOCALIZED_EN_CA = '/localized-slug/en_ca/page3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        /** @var Page $page3 */
        $page3 = $this->getReference(LoadPageData::PAGE_3);

        $anonymousSlug = $this
            ->createSlug($manager, self::SLUG_URL_ANONYMOUS, 'oro_cms_frontend_page_view', ['id' => $page->getId()]);
        $anonymousSlug->setSlugPrototype('anonymous');
        $page->addSlug($anonymousSlug);

        $this->createSlug($manager, self::SLUG_URL_USER, 'oro_customer_frontend_customer_user_index', []);
        $this->createSlug($manager, self::SLUG_TEST_DUPLICATE_URL, 'oro_customer_frontend_customer_user_index', []);
        $this->createSlug(
            $manager,
            self::SLUG_TEST_DUPLICATE_URL,
            'oro_cms_frontend_page_view',
            ['id' => $page->getId()],
            self::SLUG_TEST_DUPLICATE_REFERENCE
        );
        $pageSlug = $this->createSlug($manager, self::SLUG_URL_PAGE, 'oro_customer_frontend_customer_user_index', []);
        $pageSlug->setSlugPrototype('page');

        $page2Slug = $this->createSlug(
            $manager,
            self::SLUG_URL_PAGE_2,
            'oro_cms_frontend_page_view',
            ['id' => $page2->getId()]
        );
        $page2Slug->setSlugPrototype('page2');

        $this->createSlug($manager, self::SLUG_TEST_ONLY, '__test__', []);

        $this->createSlug(
            $manager,
            self::SLUG_URL_LOCALIZATION_1,
            'oro_product_frontend_product_view',
            ['id' => $page->getId()]
        );

        $this->createSlug(
            $manager,
            self::SLUG_URL_LOCALIZATION_2,
            'oro_product_frontend_product_view',
            ['id' => $page->getId()],
            null,
            $this->getReference('es')
        );

        $this->createSlug(
            $manager,
            self::PAGE_3_DEFAULT,
            'oro_cms_frontend_page_view',
            ['id' => $page3->getId()]
        );
        $this->createSlug(
            $manager,
            self::PAGE_3_LOCALIZED_EN_CA,
            'oro_cms_frontend_page_view',
            ['id' => $page3->getId()],
            null,
            $this->getReference('en_CA')
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $url
     * @param string $routeName
     * @param array $routeParameters
     * @param null|string $reference
     * @param null|Localization $localization
     * @return Slug
     */
    protected function createSlug(
        ObjectManager $manager,
        $url,
        $routeName,
        array $routeParameters,
        $reference = null,
        Localization $localization = null
    ) {
        $slug = new Slug();
        $slug->setUrl($url);
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);

        if (null !== $localization) {
            $slug->setLocalization($localization);
        }

        $manager->persist($slug);
        $this->addReference($reference ?: $url, $slug);

        return $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPageData::class,
            LoadLocalizationData::class
        ];
    }
}
