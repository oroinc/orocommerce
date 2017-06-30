<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class UpdatePriceListExtendEntity implements
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
        $this->updateConfig();
    }

    private function updateConfig()
    {
        $storage = $this->dataStorageExtension;
        $stateData = [];

        $dumper = $this->container->get('oro_entity_extend.tools.dumper');
        $dumper->updateConfig(function (ConfigInterface $config) use ($storage, $stateData) {
            $configId  = $config->getId();
            $className = $configId->getClassName();

            $isSupported = $className === PriceList::class;

            if ($isSupported) {
                $stateData = $storage->get('initial_entity_config_state', []);
                $stateData['entities'][$className] = $config->get('state');
            }

            return $isSupported;
        });

        $this->dataStorageExtension->set('initial_entity_config_state', $stateData);

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $provider = $configManager->getProvider('extend');

        $entityConfig = $provider->getConfig(PriceList::class);
        $entityConfig->set('is_extend', true);
        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $configManager->persist($entityConfig);
    }
}
