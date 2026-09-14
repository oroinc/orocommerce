<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LoadWebCatalogPageVariantsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * The first page has a variant on two nodes of a different level, so that the node level
     * takes part in choosing the variant a canonical URL is built from.
     */
    private static array $data = [
        LoadContentNodesData::CATALOG_1_ROOT => LoadPageData::PAGE_1,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 => LoadPageData::PAGE_2,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1 => LoadPageData::PAGE_1,
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadContentNodesData::class,
            LoadScopeData::class,
            LoadSlugsData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);

        foreach (self::$data as $nodeReference => $pageReference) {
            /** @var Page $page */
            $page = $this->getReference($pageReference);
            /** @var ContentNode $node */
            $node = $this->getReference($nodeReference);

            $slug = new Slug();
            $slug->setUrl('/' . $nodeReference);
            $slug->setRouteName('oro_cms_frontend_page_view');
            $slug->setRouteParameters(['id' => $page->getId()]);
            $slug->addScope($scope);
            $slug->setOrganization($page->getOrganization());
            $manager->persist($slug);

            $variant = new ContentVariant();
            $variant->setType(CmsPageContentVariantType::TYPE);
            $variant->setCmsPage($page);
            $variant->setNode($node);
            $variant->addSlug($slug);
            $manager->persist($variant);
        }

        $manager->flush();
    }
}
