<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadScopeData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const CATALOG_1_SCOPE = 'catalog_1_scope';
    public const CATALOG_2_SCOPE = 'catalog_2_scope';

    /**
     * @var array
     */
    public static $scopesData = [
        self::CATALOG_1_SCOPE => [
            'web_catalog' => LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING
        ],
        self::CATALOG_2_SCOPE => [
            'web_catalog' => LoadWebCatalogData::CATALOG_2
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->container->get('oro_scope.scope_manager');
        foreach (self::$scopesData as $referenceName => $scopeData) {
            $scope = $scopeManager->findOrCreate(
                'web_content',
                [
                    ScopeWebCatalogProvider::WEB_CATALOG => $this->getReference(
                        $scopeData['web_catalog']
                    )
                ]
            );
            $this->setReference($referenceName, $scope);
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class
        ];
    }
}
