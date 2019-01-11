<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadContentNodesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const CATALOG_1_ROOT = 'web_catalog.node.1.root';
    const CATALOG_1_ROOT_WITH_CMS_PAGE_VARIANT = 'web_catalog.node.1.cms_page_variant';
    const CATALOG_1_ROOT_WITH_SYSTEM_PAGE_VARIANT = 'web_catalog.node.1.system_page_variant';
    const CATALOG_2_ROOT = 'web_catalog.node.2.root';
    const CATALOG_2_ROOT_WITH_CMS_PAGE_VARIANT = 'web_catalog.node.2.cms_page_variant';
    const CATALOG_2_ROOT_WITH_SYSTEM_PAGE_VARIANT = 'web_catalog.node.2.system_page_variant';

    /**
     * @var array
     */
    public static $data = [
        LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING => [
            self::CATALOG_1_ROOT => [
                'parent' => null,
                'parentScopeUsed' => false,
                'scope' => LoadScopeData::CATALOG_1_SCOPE
            ],
            self::CATALOG_1_ROOT_WITH_CMS_PAGE_VARIANT => [
                'parent' => self::CATALOG_1_ROOT,
                'parentScopeUsed' => true,
                'contentVariantType' => CmsPageContentVariantType::TYPE,
                'scope' => LoadScopeData::CATALOG_1_SCOPE
            ],
            self::CATALOG_1_ROOT_WITH_SYSTEM_PAGE_VARIANT => [
                'parent' => self::CATALOG_1_ROOT,
                'parentScopeUsed' => true,
                'contentVariantType' => SystemPageContentVariantType::TYPE,
                'scope' => LoadScopeData::CATALOG_1_SCOPE
            ]
        ],
        LoadWebCatalogData::CATALOG_2 => [
            self::CATALOG_2_ROOT => [
                'parent' => null,
                'parentScopeUsed' => false,
                'scope' => LoadScopeData::CATALOG_2_SCOPE
            ],
            self::CATALOG_2_ROOT_WITH_CMS_PAGE_VARIANT => [
                'parent' => self::CATALOG_2_ROOT,
                'parentScopeUsed' => true,
                'contentVariantType' => CmsPageContentVariantType::TYPE,
                'scope' => LoadScopeData::CATALOG_2_SCOPE
            ],
            self::CATALOG_2_ROOT_WITH_SYSTEM_PAGE_VARIANT => [
                'parent' => self::CATALOG_2_ROOT,
                'parentScopeUsed' => true,
                'contentVariantType' => SystemPageContentVariantType::TYPE,
                'scope' => LoadScopeData::CATALOG_2_SCOPE
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class,
            LoadPageDataWithSlug::class,
            LoadScopeData::class
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
                $url = new LocalizedFallbackValue();
                $url->setString($nodeReference);
                $node->addLocalizedUrl($url);
                if (!empty($nodeData['parent'])) {
                    /** @var ContentNode $parentNode */
                    $parentNode = $this->getReference($nodeData['parent']);
                    $node->setParentNode($parentNode);
                }

                $node->addScope($this->getReference($nodeData['scope']));

                if (isset($nodeData['contentVariantType'])) {
                    switch ($nodeData['contentVariantType']) {
                        case SystemPageContentVariantType::TYPE:
                            $this->setSystemPageContentVariant($manager, $node, $nodeData);
                            break;
                        case CmsPageContentVariantType::TYPE:
                            $this->setLandingPageContentVariant($manager, $node, $nodeData);
                            break;
                        default:
                            break;
                    }
                }

                $node->setParentScopeUsed($nodeData['parentScopeUsed']);
                $manager->persist($node);
                $this->setReference($nodeReference, $node);
            }
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param ContentNode   $contentNode
     * @param array         $nodeData
     */
    private function setSystemPageContentVariant(ObjectManager $manager, ContentNode $contentNode, array $nodeData)
    {
        $contentVariant = new ContentVariant();
        $contentVariant->setType(SystemPageContentVariantType::TYPE);
        $contentVariant->setSystemPageRoute('oro_frontend_root');
        $contentVariant->setNode($contentNode);
        $contentVariant->addScope($this->getReference($nodeData['scope']));

        $manager->persist($contentVariant);
    }

    /**
     * @param ObjectManager $manager
     * @param ContentNode   $contentNode
     * @param array         $nodeData
     */
    private function setLandingPageContentVariant(ObjectManager $manager, ContentNode $contentNode, array $nodeData)
    {
        $contentVariant = new ContentVariant();
        $contentVariant->setType(CmsPageContentVariantType::TYPE);
        $contentVariant->setCmsPage($this->getReference(LoadPageDataWithSlug::PAGE_1));
        $contentVariant->setNode($contentNode);
        $contentVariant->addScope($this->getReference($nodeData['scope']));

        $manager->persist($contentVariant);
    }
}
