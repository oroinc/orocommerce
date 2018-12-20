<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Web catalog demo data
 */
class LoadWebCatalogDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use UserUtilityTrait;

    const DEFAULT_WEB_CATALOG_NAME = 'Default Web Catalog';
    const DEFAULT_WEB_CATALOG_DESC= 'Default Web Catalog description';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryDemoData::class,
            LoadSegmentsForWebCatalogDemoData::class,
        ];
    }
    
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = $this->loadWebCatalogData($manager);
        $this->enableWebCatalog($webCatalog);
        $this->generateCache($webCatalog);
    }

    /**
     * @param ObjectManager $manager
     * @return WebCatalog
     */
    protected function loadWebCatalogData(ObjectManager $manager)
    {
        $webCatalog = $this->createCatalog($manager);

        $contentNodes = $this->getWebCatalogData();
        $this->loadContentNodes($manager, $webCatalog, $contentNodes);

        $manager->flush();

        return $webCatalog;
    }

    /**
     * @param WebCatalog $webCatalog
     */
    protected function enableWebCatalog(WebCatalog $webCatalog)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(OroWebCatalogExtension::ALIAS . '.web_catalog', $webCatalog->getId());

        $configManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return WebCatalog
     */
    protected function createCatalog(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $webCatalog = new WebCatalog();
        $webCatalog->setName(self::DEFAULT_WEB_CATALOG_NAME);
        $webCatalog->setDescription(self::DEFAULT_WEB_CATALOG_DESC);
        $webCatalog->setOwner($businessUnit);
        $webCatalog->setOrganization($organization);

        $manager->persist($webCatalog);
        $manager->flush($webCatalog);

        return $webCatalog;
    }

    /**
     * @param ObjectManager $manager
     * @param WebCatalog $webCatalog
     * @param array $nodes
     * @param ContentNode $parent
     */
    protected function loadContentNodes(
        ObjectManager $manager,
        WebCatalog $webCatalog,
        array $nodes,
        ContentNode $parent = null
    ) {
        foreach ($nodes as $name => $contentNode) {
            $node = new ContentNode();
            $node->setWebCatalog($webCatalog);
            $title = new LocalizedFallbackValue();
            $title->setString($contentNode['defaultTitle']);
            $node->setDefaultTitle($title);
            $slug = new LocalizedFallbackValue();
            $slug->setString($contentNode['defaultSlugPrototype']);
            $node->setDefaultSlugPrototype($slug);

            if ($parent) {
                $node->setParentNode($parent);
            }

            $isParentScopeUsed = !empty($contentNode['parentScopeUsed']);
            $node->setParentScopeUsed($isParentScopeUsed);

            if ($isParentScopeUsed) {
                foreach ($node->getParentNode()->getScopes() as $scope) {
                    $node->addScope($scope);
                }
            } else {
                foreach ($contentNode['scopes'] as $scope) {
                    $scope = $this->getScope($scope, $webCatalog);
                    $node->addScope($scope);
                }
            }
            $this->addContentVariants($webCatalog, $contentNode['contentVariants'], $node);

            $manager->persist($node);
            $manager->flush($node);
            $this->resolveScopes($node);
            $this->generateSlugs($node);

            if (isset($contentNode['children'])) {
                $this->loadContentNodes($manager, $webCatalog, $contentNode['children'], $node);
            }

            if (isset($contentNode['isNavigationRoot'])) {
                $configManager = $this->container->get('oro_config.global');
                $configManager->set(OroWebCatalogExtension::ALIAS . '.navigation_root', $node->getId());

                $configManager->flush();
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function generateSlugs(ContentNode $contentNode)
    {
        $this->container->get('oro_web_catalog.generator.slug_generator')
            ->generate($contentNode);
    }

    /**
     * @param array $criteria
     * @param WebCatalog $webCatalog
     * @return Scope
     */
    protected function getScope(array $criteria, WebCatalog $webCatalog)
    {
        $scopeCriteria = array_merge($criteria, ['webCatalog' => $webCatalog]);

        return $this->container->get('oro_scope.scope_manager')->findOrCreate('base_scope', $scopeCriteria);
    }

    /**
     * @param string $type
     * @param array $params
     * @return ContentVariant
     */
    protected function getContentVariant($type, array $params)
    {
        $variant = new ContentVariant();

        $variant->setType($type);

        if ($type === CategoryPageContentVariantType::TYPE && method_exists($variant, 'setCategoryPageCategory')) {
            $category = $this->container->get('doctrine')
                ->getRepository(Category::class)
                ->findOneByDefaultTitle($params['title']);
            $variant->setCategoryPageCategory($category);
            $variant->setExcludeSubcategories($params['excludeSubcategories'] ?? true);
        } elseif ($type === CmsPageContentVariantType::TYPE && method_exists($variant, 'setCmsPage')) {
            $page = $this->container->get('doctrine')
                ->getRepository(Page::class)
                ->findOneByTitle($params['title']);
            $variant->setCmsPage($page);
        } elseif ($type === SystemPageContentVariantType::TYPE) {
            $variant->setSystemPageRoute($params['route']);
        } elseif ($type === ProductCollectionContentVariantType::TYPE
            && method_exists($variant, 'setProductCollectionSegment')
        ) {
            $segment = $this->container
                ->get('doctrine')
                ->getRepository(Segment::class)
                ->findOneByName($params['title']);
            $variant->setProductCollectionSegment($segment);
        }
        
        return $variant;
    }

    /**
     * @return array
     */
    protected function getWebCatalogData()
    {
        $locator = $this->container->get('file_locator');
        $fileName = $locator->locate('@OroWebCatalogBundle/Migrations/Data/Demo/ORM/data/web_catalog_data.yml');

        return Yaml::parse(file_get_contents($fileName));
    }

    /**
     * Site is available without web catalog cache, but it's performance is lower.
     * Schedule cache calculation in background by async message processor.
     *
     * @param WebCatalog $webCatalog
     */
    protected function scheduleCacheCalculation(WebCatalog $webCatalog)
    {
        /** @var MessageProducerInterface $messageProducer */
        $messageProducer = $this->container->get('oro_message_queue.client.message_producer');
        $messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, $webCatalog->getId());
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function resolveScopes(ContentNode $contentNode)
    {
        $this->container->get('oro_web_catalog.resolver.default_variant_scope')
            ->resolve($contentNode);
    }

    /**
     * @param WebCatalog $webCatalog
     * @param array $contentVariantsData
     * @param ContentNode $node
     */
    protected function addContentVariants(WebCatalog $webCatalog, array $contentVariantsData, ContentNode $node)
    {
        foreach ($contentVariantsData as $contentVariant) {
            $variant = $this->getContentVariant($contentVariant['type'], $contentVariant['params']);
            $isDefault = !empty($contentVariant['isDefault']);
            $variant->setDefault($isDefault);
            if (!$isDefault) {
                foreach ($contentVariant['scopes'] as $scope) {
                    $scope = $this->getScope($scope, $webCatalog);
                    $variant->addScope($scope);
                }
            }
            $node->addContentVariant($variant);
        }
    }

    /**
     * @param WebCatalog $webCatalog
     */
    protected function generateCache(WebCatalog $webCatalog)
    {
        $registry = $this->container->get('doctrine');
        $scopeMatcher = $this->container->get('oro_web_catalog.scope_matcher');
        $dumper = $this->container->get('oro_web_catalog.cache.dumper.content_node_tree_dumper');

        /** @var ContentNodeRepository $contentNodeRepo */
        $contentNodeRepo = $registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        $rootContentNode = $contentNodeRepo->getRootNodeByWebCatalog($webCatalog);

        $scopes = $scopeMatcher->getUsedScopes($webCatalog);

        foreach ($scopes as $scope) {
            $dumper->dump($rootContentNode, $scope);
        }
    }
}
