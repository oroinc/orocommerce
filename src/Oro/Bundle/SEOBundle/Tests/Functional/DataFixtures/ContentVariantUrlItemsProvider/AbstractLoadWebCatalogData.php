<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\ContentVariantUrlItemsProvider;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

abstract class AbstractLoadWebCatalogData extends AbstractFixture implements DependentFixtureInterface
{
    public const CONTENT_NODE_SLUG = '/content-node-slug';
    public const CONTENT_NODE = 'content-node';
    public const CONTENT_NODE_TITLE = 'Content node title';

    protected array $nodesConfigs = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        if (empty($this->nodesConfigs)) {
            return;
        }

        foreach ($this->nodesConfigs as $nodeIndex => $nodeConfig) {
            $nodeTitle = new LocalizedFallbackValue();
            $nodeTitle->setString(self::CONTENT_NODE_TITLE . ($nodeIndex+1));

            $node = new ContentNode();
            $node->setWebCatalog($webCatalog);
            $node->setRewriteVariantTitle(true);
            $node->setDefaultTitle($nodeTitle);

            $slug = new Slug();
            $slug->setUrl(sprintf('%s-%s', self::CONTENT_NODE_SLUG, ($nodeIndex+1)));
            $slug->setRouteName($this->getRoute());
            $slug->setRouteParameters(['id' => ($nodeIndex+1)]);
            $slug->setOrganization($webCatalog->getOrganization());
            foreach ($nodeConfig['nodeScopes'] as $nodeScopeRef) {
                /** @var Scope $scope */
                $scope = $this->getReference($nodeScopeRef);
                $scope->setWebCatalog($webCatalog);
                $node->addScope($scope);
                $slug->addScope($scope);
            }

            $entitySetterMethod = $this->getEntitySetterMethod();

            foreach ($nodeConfig['pagesPerScope'] as $pageRef => $scopeRef) {
                /** @var Scope $scope */
                $scope = $this->getReference($scopeRef);
                $scope->setWebCatalog($webCatalog);

                $variant = new ContentVariant();
                $variant->setType($this->getContentVariantType());
                $variant->setNode($node);
                $variant->addScope($scope);

                /** @var Page $page */
                $page = $this->getReference($pageRef);

                if (EntityPropertyInfo::methodExists($variant, $entitySetterMethod)) {
                    $variant->$entitySetterMethod($page);
                }

                $variant->addSlug($slug);

                $manager->persist($slug);
                $manager->persist($variant);
            }

            $manager->persist($node);
            $this->setReference(self::CONTENT_NODE . '-' . ($nodeIndex+1), $node);

            if (0 !== $nodeIndex) {
                $node->setParentNode($this->getReference(self::CONTENT_NODE . '-1'));
                $node->setParentScopeUsed(false);
            }
        }

        $manager->flush();
    }

    abstract protected function getRoute(): string;
    abstract protected function getContentVariantType(): string;
    abstract protected function getEntitySetterMethod(): string;
}
