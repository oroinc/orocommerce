<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadPageData;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM\AbstractLoadWebCatalogDemoData;
use Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM\LoadWebCatalogDemoData;

/**
 * Adds an Accessibility content node to the Default Web Catalog and sets it
 * as the default value for the {@see Configuration::ACCESSIBILITY_PAGE} system configuration option.
 */
class LoadAccessibilityPageDemoData extends AbstractLoadWebCatalogDemoData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadWebCatalogDemoData::class,
            LoadPageData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $webCatalog = $this->getWebCatalog($manager);
        if (!$webCatalog) {
            return;
        }

        $rootNode = $this->getRootNode($manager, $webCatalog);
        if (!$rootNode) {
            return;
        }

        $this->loadContentNodes(
            $manager,
            $webCatalog,
            $this->getWebCatalogData(
                '@OroCMSBundle/Migrations/Data/Demo/ORM/data/accessibility_page_data.yml'
            ),
            $rootNode
        );

        $manager->flush();

        $accessibilityNode = $this->getAccessibilityNode($manager);
        if ($accessibilityNode) {
            $this->setAccessibilityPageConfig($accessibilityNode);
        }

        $this->generateCache($webCatalog);
    }

    private function getWebCatalog(ObjectManager $manager): ?WebCatalog
    {
        return $manager->getRepository(WebCatalog::class)
            ->findOneBy(['name' => self::DEFAULT_WEB_CATALOG_NAME]);
    }

    private function getRootNode(ObjectManager $manager, WebCatalog $webCatalog): ?ContentNode
    {
        return $manager->getRepository(ContentNode::class)
            ->findOneBy(['parentNode' => null, 'webCatalog' => $webCatalog]);
    }

    private function getAccessibilityNode(ObjectManager $manager): ?ContentNode
    {
        return $manager->getRepository(ContentNode::class)
            ->createQueryBuilder('node')
            ->innerJoin('node.titles', 't')
            ->where('t.string = :title')
            ->setParameter(':title', 'Accessibility', Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function setAccessibilityPageConfig(ContentNode $node): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE,
            $node->getId()
        );
        $configManager->flush();
    }
}
