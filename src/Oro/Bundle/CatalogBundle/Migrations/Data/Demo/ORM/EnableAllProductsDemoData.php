<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\DependencyInjection\Configuration as CatalogConfig;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class EnableAllProductsDemoData just enable All products page on demo instance
 */
class EnableAllProductsDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(CatalogConfig::getConfigKeyByName(CatalogConfig::ALL_PRODUCTS_PAGE_ENABLED), true);

        $configManager->flush();
    }
}
