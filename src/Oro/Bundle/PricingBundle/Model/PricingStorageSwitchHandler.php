<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

/**
 * Handles pricing storage switches for customer, customer group and config levels.
 */
class PricingStorageSwitchHandler implements PricingStorageSwitchHandlerInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PriceListConfigConverter
     */
    private $configConverter;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $registry,
        PriceListConfigConverter $configConverter
    ) {
        $this->configManager = $configManager;
        $this->registry = $registry;
        $this->configConverter = $configConverter;
    }

    public function moveAssociationsForFlatPricingStorage(): void
    {
        $this->deleteNonFirstPriceListAssociations(
            $this->getTableName(PriceListToCustomer::class),
            'customer_id'
        );
        $this->deleteNonFirstPriceListAssociations(
            $this->getTableName(PriceListToCustomerGroup::class),
            'customer_group_id'
        );
        $this->moveConfigLevelPriceListToFlat();
    }

    public function moveAssociationsForCombinedPricingStorage(): void
    {
        $this->moveConfigLevelPriceListToCombined();
    }

    /**
     * Leave only one price list to entity association. Delete all other associations.
     *
     * Used native SQL query to allow JOIN to sub-select for performance reasons.
     * This query is inseparable part of switch handler and should not be used outside of this class, so it is isolated
     * in the current class.
     * Any change to switch logic will lead to query changes and vise versa, so Single Responsibility principle
     * isn't broken here.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteNonFirstPriceListAssociations(string $table, string $associationColumn)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(PriceList::class);
        $connection = $em->getConnection();

        $minIdsQuerySQL = <<<MIN_SQL
SELECT id
FROM %1\$s rel
INNER JOIN (
    SELECT
       MIN(sort_order) as min_sort_order, 
       %2\$s, 
       website_id
    FROM %1\$s
    GROUP BY %2\$s, website_id
) t1
ON 
    t1.website_id = rel.website_id 
    AND t1.%2\$s = rel.%2\$s 
    AND t1.min_sort_order = rel.sort_order
MIN_SQL;

        $stmt = $connection->prepare(sprintf($minIdsQuerySQL, $table, $associationColumn));
        $stmt->execute();
        $minIds = $stmt->fetchAll(FetchMode::COLUMN);

        if ($minIds) {
            $deleteQb = $connection->createQueryBuilder();
            $deleteQb->delete($table)
                ->where($deleteQb->expr()->notIn('id', ':ids'))
                ->setParameter('ids', $minIds, Connection::PARAM_INT_ARRAY);

            $deleteQb->execute();
        }
    }

    private function moveConfigLevelPriceListToFlat()
    {
        $defaultPriceLists = $this->configConverter->convertFromSaved(
            $this->configManager->get('oro_pricing.default_price_lists')
        );
        if ($defaultPriceLists) {
            $defaultPriceListConfig = reset($defaultPriceLists);
            $this->configManager->set(
                'oro_pricing.default_price_list',
                $defaultPriceListConfig->getPriceList()->getId()
            );
            $this->configManager->reset('oro_pricing.default_price_lists');
            $this->configManager->flush();
        }
    }

    private function moveConfigLevelPriceListToCombined(): void
    {
        $defaultPriceList = $this->configManager->get('oro_pricing.default_price_list');
        $defaultPriceLists = [];
        if ($defaultPriceList) {
            $priceList = $this->registry
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $defaultPriceList);

            if ($priceList) {
                $priceListConfig = new PriceListConfig($priceList, 0, true);
                $defaultPriceLists = [$priceListConfig];
            }
        }

        $this->configManager->set('oro_pricing.default_price_list', null);
        $this->configManager->set('oro_pricing.default_price_lists', $defaultPriceLists);
        $this->configManager->flush();
    }

    private function getTableName(string $className): string
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass($className);

        return $em->getClassMetadata($className)->getTableName();
    }
}
