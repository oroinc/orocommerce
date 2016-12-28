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
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

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
        return [LoadCategoryDemoData::class];
    }
    
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = $this->loadWebCatalogData($manager);
        $this->enableWebCatalog($webCatalog);
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

            $node->setParentScopeUsed($contentNode['parentScopeUsed']);

            foreach ($contentNode['scopes'] as $scope) {
                $scope = $this->getScope($scope, $webCatalog);
                $node->addScope($scope);
            }

            foreach ($contentNode['contentVariants'] as $contentVariant) {
                $variant = $this->getContentVariant($contentVariant['type'], $contentVariant['params']);
                $variant->setDefault($contentVariant['isDefault']);
                foreach ($contentVariant['scopes'] as $scope) {
                    $scope = $this->getScope($scope, $webCatalog);
                    $variant->addScope($scope);
                }
                $node->addContentVariant($variant);
            }

            if ($parent) {
                $node->setParentNode($parent);
            }

            $this->generateSlugs($node);
            $manager->persist($node);

            if ($contentNode['children']) {
                $this->loadContentNodes($manager, $webCatalog, $contentNode['children'], $node);
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

        if ($type === CategoryPageContentVariantType::TYPE) {
            $category = $this->container->get('doctrine')
                ->getRepository(Category::class)
                ->findOneByDefaultTitle($params['title']);
            $variant->setCategoryPageCategory($category);
        } elseif ($type === CmsPageContentVariantType::TYPE) {
            $page = $this->container->get('doctrine')
                ->getRepository(Page::class)
                ->findOneByTitle($params['title']);
            $variant->setCmsPage($page);
        }
        
        return $variant;
    }

    /**
     * @return array
     */
    protected function getWebCatalogData()
    {
        $fileName = __DIR__ . '/data/web_catalog_data.yml';

        return Yaml::parse(file_get_contents($fileName));
    }
}
