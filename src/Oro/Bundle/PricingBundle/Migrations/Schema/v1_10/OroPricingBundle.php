<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroPricingBundle implements
    Migration,
    ContainerAwareInterface,
    DataStorageExtensionAwareInterface
{
    use ContainerAwareTrait;

    /** @var DataStorageExtension */
    private $dataStorageExtension;

    /**
     * {@inheritdoc}
     */
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension)
    {
        $this->dataStorageExtension = $dataStorageExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateExtendEntity();

        $queries->addQuery(new OroPriceListStrategyQuery());
    }

    /**
     * Update Extended Entity
     */
    private function updateExtendEntity()
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $provider = $configManager->getProvider('extend');
        $entityConfig = $provider->getConfig(PriceList::class);
        $entityConfig->set('is_extend', true);
        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);

        $configManager->persist($entityConfig);
    }
}
