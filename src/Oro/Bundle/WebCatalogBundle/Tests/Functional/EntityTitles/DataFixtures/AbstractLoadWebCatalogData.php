<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

abstract class AbstractLoadWebCatalogData extends AbstractFixture implements DependentFixtureInterface
{
    const CONTENT_NODE_SLUG = '/content-node-slug';
    const CONTENT_NODE = 'content-node';
    const CONTENT_NODE_TITLE = 'Content node title';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);

        /** @var SluggableInterface[] $entities */
        $entities = $this->getEntity();
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);

        $nodeTitle = new LocalizedFallbackValue();
        $nodeTitle->setString(self::CONTENT_NODE_TITLE);

        $node = new ContentNode();
        $node->setWebCatalog($webCatalog);
        $node->setRewriteVariantTitle(true);
        $node->setDefaultTitle($nodeTitle);
        $node->addScope($scope);

        $entitySetterMethod = $this->getEntitySetterMethod();

        foreach ($entities as $entity) {
            $variant = new ContentVariant();
            $variant->setType($this->getContentVariantType());
            $variant->setNode($node);
            $variant->addScope($scope);

            $slug = new Slug();
            $slug->setUrl(sprintf('%s-%s', self::CONTENT_NODE_SLUG, $entity->getId()));
            $slug->setRouteName($this->getRoute());
            $slug->setRouteParameters(['id' => $entity->getId()]);
            $slug->addScope($scope);
            $slug->setOrganization($webCatalog->getOrganization());

            if (EntityPropertyInfo::methodExists($variant, $entitySetterMethod)) {
                $variant->$entitySetterMethod($entity);
            }

            $variant->addSlug($slug);

            $manager->persist($slug);
            $manager->persist($variant);
        }

        $manager->persist($node);
        $this->setReference(self::CONTENT_NODE, $node);

        $manager->flush();
    }

    /**
     * @return string
     */
    abstract protected function getRoute();

    /**
     * @return string
     */
    abstract protected function getContentVariantType();

    /**
     * @return string
     */
    abstract protected function getEntitySetterMethod();

    /**
     * @return SluggableInterface|SluggableInterface[]
     */
    abstract protected function getEntity();
}
