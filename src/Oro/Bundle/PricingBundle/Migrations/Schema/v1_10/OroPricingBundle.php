<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class OroPricingBundle implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new OroPriceListStrategyQuery());

        $this->updateConfig();
    }

    public function updateConfig()
    {
        $dumper = $this->container->get('oro_entity_extend.tools.dumper');
        $dumper->updateConfig(function (ConfigInterface $config) {
            $configId  = $config->getId();
            $className = $configId->getClassName();

            return $className === PriceList::class;
        });
    }
}
