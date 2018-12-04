<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadWebCatalogData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const CATALOG_1_USE_IN_ROUTING = 'web_catalog.1';
    const CATALOG_2 = 'web_catalog.2';
    const CATALOG_3 = 'web_catalog.3';

    public static $data = [
        self::CATALOG_1_USE_IN_ROUTING => [
            'use_in_routing' => true
        ],
        self::CATALOG_2 => [
            'use_in_routing' => false
        ],
        self::CATALOG_3 => [
            'use_in_routing' => false
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$data as $catalogReference => $catalogData) {
            $webCatalog = $this->createCatalog($catalogReference);
            $manager->persist($webCatalog);
            $this->setReference($catalogReference, $webCatalog);
        }

        $manager->flush();

        foreach (self::$data as $catalogReference => $catalogData) {
            if ($catalogData['use_in_routing']) {
                $configManager = $this->container->get('oro_config.global');
                $configManager->set(
                    OroWebCatalogExtension::ALIAS. ConfigManager::SECTION_MODEL_SEPARATOR . 'web_catalog',
                    $this->getReference($catalogReference)->getId()
                );
            }
        }

        $manager->flush();
    }

    /**
     * @param string $catalogName
     * @return WebCatalog
     */
    private function createCatalog($catalogName)
    {
        $catalog = new WebCatalog();
        $catalog->setName($catalogName);
        $catalog->setDescription($catalogName . ' description');

        return $catalog;
    }
}
