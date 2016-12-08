<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
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
    protected static $data = [
        LoadWebCatalogData::CATALOG_1 => [
            self::CATALOG_1_ROOT => [
                'parent' => null
            ],
            self::CATALOG_1_ROOT_SUBNODE_1 => [
                'parent' => self::CATALOG_1_ROOT
            ],
            self::CATALOG_1_ROOT_SUBNODE_1_1 => [
                'parent' => self::CATALOG_1_ROOT_SUBNODE_1
            ],
            self::CATALOG_1_ROOT_SUBNODE_1_2 => [
                'parent' => self::CATALOG_1_ROOT_SUBNODE_1
            ],
            self::CATALOG_1_ROOT_SUBNODE_2 => [
                'parent' => self::CATALOG_1_ROOT
            ]
        ],
        LoadWebCatalogData::CATALOG_2 => [
            self::CATALOG_2_ROOT => [
                'parent' => null
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
                $node->setName($nodeReference);
                if (!empty($nodeData['parent'])) {
                    /** @var ContentNode $parentNode */
                    $parentNode = $this->getReference($nodeData['parent']);
                    $node->setParentNode($parentNode);
                }
                $manager->persist($node);
                $this->setReference($nodeReference, $node);
            }
        }

        $manager->flush();
    }
}
