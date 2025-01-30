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
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadSlugsData extends AbstractFixture implements DependentFixtureInterface
{
    public const SLUG_URL_ANONYMOUS = '/slug/anonymous';
    public const SLUG_URL_USER = '/slug/customer';
    public const SLUG_URL_PAGE = '/slug/page';
    public const SLUG_URL_PAGE_2 = '/slug/page2';
    public const SLUG_URL_LOCALIZATION_1 = '/slug/en/page';
    public const SLUG_URL_LOCALIZATION_2 = '/slug/es/page';
    public const SLUG_TEST_URL = '/slug/first';
    public const SLUG_TEST_REFERENCE = 'reference:/slug/first';
    public const SLUG_TEST_ONLY = '__test-only__';
    public const PAGE_3_DEFAULT = '/localized-slug/en/page3';
    public const PAGE_3_LOCALIZED_EN_CA = '/localized-slug/en_ca/page3';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadPageData::class, LoadLocalizationData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        /** @var Page $page3 */
        $page3 = $this->getReference(LoadPageData::PAGE_3);

        $anonymousSlug = $this->createSlug(
            $manager,
            self::SLUG_URL_ANONYMOUS,
            'oro_cms_frontend_page_view',
            ['id' => $page->getId()]
        );
        $anonymousSlug->setSlugPrototype('anonymous');
        $page->addSlug($anonymousSlug);

        $this->createSlug($manager, self::SLUG_URL_USER, 'oro_customer_frontend_customer_user_index', []);
        $this->createSlug(
            $manager,
            self::SLUG_TEST_URL,
            'oro_cms_frontend_page_view',
            [],
            self::SLUG_TEST_REFERENCE
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

        $page3->addSlug($this->createSlug(
            $manager,
            self::PAGE_3_DEFAULT,
            'oro_cms_frontend_page_view',
            ['id' => $page3->getId()]
        ));
        $page3->addSlug($this->createSlug(
            $manager,
            self::PAGE_3_LOCALIZED_EN_CA,
            'oro_cms_frontend_page_view',
            ['id' => $page3->getId()],
            null,
            $this->getReference('en_CA')
        ));
        $manager->persist($page3);

        $manager->flush();
    }

    private function createSlug(
        ObjectManager $manager,
        string        $url,
        string        $routeName,
        array         $routeParameters,
        ?string       $reference = null,
        ?Localization $localization = null
    ): Slug {
        $slug = new Slug();
        $slug->setUrl($url);
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);
        $slug->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));

        if (null !== $localization) {
            $slug->setLocalization($localization);
        }

        $manager->persist($slug);
        if ($reference) {
            $this->addReference($reference, $slug);
        }
        $this->addReference($url, $slug);

        return $slug;
    }
}
