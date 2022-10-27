<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadContentNodesData extends AbstractFixture implements DependentFixtureInterface
{
    const CATALOG_1_ROOT = 'web_catalog.node.1.root';
    const CATALOG_1_ROOT_SUBNODE_1 = 'web_catalog.node.1.1';
    const CATALOG_1_ROOT_SUBNODE_1_1 = 'web_catalog.node.1.1.1';
    const CATALOG_1_ROOT_SUBNODE_1_2 = 'web_catalog.node.1.1.2';
    const CATALOG_1_ROOT_SUBNODE_2 = 'web_catalog.node.1.2';
    const CATALOG_2_ROOT = 'web_catalog.node.2.root';

    /**
     * @var array
     */
    public static $data = [
        LoadWebCatalogData::CATALOG_1 => [
            self::CATALOG_1_ROOT => [
                'parent' => null,
                'parentScopeUsed' => false
            ],
            self::CATALOG_1_ROOT_SUBNODE_1 => [
                'parent' => self::CATALOG_1_ROOT,
                'parentScopeUsed' => true
            ],
            self::CATALOG_1_ROOT_SUBNODE_1_1 => [
                'parent' => self::CATALOG_1_ROOT_SUBNODE_1,
                'parentScopeUsed' => true
            ],
            self::CATALOG_1_ROOT_SUBNODE_1_2 => [
                'parent' => self::CATALOG_1_ROOT_SUBNODE_1,
                'parentScopeUsed' => false
            ],
            self::CATALOG_1_ROOT_SUBNODE_2 => [
                'parent' => self::CATALOG_1_ROOT,
                'parentScopeUsed' => false
            ]
        ],
        LoadWebCatalogData::CATALOG_2 => [
            self::CATALOG_2_ROOT => [
                'parent' => null,
                'parentScopeUsed' => false
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$data as $webCatalogReference => $nodes) {
            /** @var WebCatalog $webCatalog */
            $webCatalog = $this->getReference($webCatalogReference);
            foreach ($nodes as $nodeReference => $nodeData) {
                $node = new ContentNode();
                $node->setWebCatalog($webCatalog);
                $title = new LocalizedFallbackValue();
                $title->setString($nodeReference);
                $node->addTitle($title);
                $node->addSlugPrototype((new LocalizedFallbackValue())->setString($nodeReference));
                $node->addLocalizedUrl((new LocalizedFallbackValue())->setText('/' . $nodeReference));
                if (!empty($nodeData['parent'])) {
                    /** @var ContentNode $parentNode */
                    $parentNode = $this->getReference($nodeData['parent']);
                    $node->setParentNode($parentNode);
                }

                $node->setParentScopeUsed($nodeData['parentScopeUsed']);
                $manager->persist($node);
                $this->setReference($nodeReference, $node);
            }
        }

        $manager->flush();
    }
}
