<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_7;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroPriceListPriorityQuery extends ParametrizedMigrationQuery
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);
        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->updatePriceListAccountGroupPriorities($logger, $dryRun);
        $this->updatePriceListAccountPriorities($logger, $dryRun);
        $this->updatePriceListWebsitePriorities($logger, $dryRun);
        $this->updatePriceListConfigPriority($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updatePriceListConfigPriority(LoggerInterface $logger, $dryRun = false)
    {
        /** @var ConfigManager */
        $configManager = $this->container->get('oro_config.global');

        $defaultPriceLists =  $configManager->get('oro_pricing.default_price_lists');

        usort(
            $defaultPriceLists,
            function ($a, $b) {
                return ($a['priority'] < $b['priority']) ? -1 : 1;
            }
        );

        $priorities = [];
        foreach ($defaultPriceLists as $priceList) {
            $priorities[] = $priceList['priority'];
        }

        $priceLists = [];
        foreach ($defaultPriceLists as $defaultPriceList) {
            $priceList = $this->container->get('doctrine')
                ->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class)
                ->find($defaultPriceList['priceList']);

            $priceLists[] = new PriceListConfig($priceList, array_pop($priorities), $defaultPriceList['mergeAllowed']);
        }

        $configManager->set('oro_pricing.default_price_lists', $priceLists);
        $configManager->flush();
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updatePriceListAccountGroupPriorities(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT website_id, account_group_id, priority, price_list_id
                FROM oro_price_list_to_acc_group
                ORDER BY website_id, account_group_id, priority';

        $oldPriorities = $this->fetchData($logger, $sql);
        $newPriorities = $this->collectPriorities($oldPriorities, 'website_id', 'account_group_id');

        $sql = 'UPDATE oro_price_list_to_acc_group SET priority = :priority 
                WHERE website_id = :websiteId AND account_group_id = :accountGroupId AND price_list_id = :priceListId';

        $types = [
            'priority' => 'integer',
            'websiteId' => 'integer',
            'accountGroupId' => 'integer',
            'priceListId' => 'integer',
        ];

        foreach ($oldPriorities as $value) {
            $params = [
                'priority' => array_pop($newPriorities[$value['website_id']][$value['account_group_id']]),
                'websiteId' => $value['website_id'],
                'accountGroupId' => $value['account_group_id'],
                'priceListId' => $value['price_list_id']
            ];

            $this->saveData($logger, $sql, $params, $types, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updatePriceListAccountPriorities(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT website_id, account_id, priority, price_list_id
                FROM oro_price_list_to_account
                ORDER BY website_id, account_id, priority';

        $oldPriorities = $this->fetchData($logger, $sql);
        $newPriorities = $this->collectPriorities($oldPriorities, 'website_id', 'account_id');

        $sql = 'UPDATE oro_price_list_to_account SET priority = :priority 
                WHERE website_id = :websiteId AND account_id = :accountId AND price_list_id = :priceListId';

        $types = [
            'priority' => 'integer',
            'websiteId' => 'integer',
            'accountId' => 'integer',
            'priceListId' => 'integer',
        ];

        foreach ($oldPriorities as $value) {
            $params = [
                'priority' => array_pop($newPriorities[$value['website_id']][$value['account_id']]),
                'websiteId' => $value['website_id'],
                'accountId' => $value['account_id'],
                'priceListId' => $value['price_list_id']
            ];

            $this->saveData($logger, $sql, $params, $types, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updatePriceListWebsitePriorities(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT website_id, priority, price_list_id
                FROM oro_price_list_to_website
                ORDER BY website_id, priority';

        $oldPriorities = $this->fetchData($logger, $sql);
        $newPriorities = $this->collectPriorities($oldPriorities, 'website_id');

        $sql = 'UPDATE oro_price_list_to_website SET priority = :priority 
                WHERE website_id = :websiteId AND price_list_id = :priceListId';

        $types = [
            'priority' => 'integer',
            'websiteId' => 'integer',
            'priceListId' => 'integer',
        ];

        foreach ($oldPriorities as $value) {
            $params = [
                'priority' => array_pop($newPriorities[$value['website_id']]),
                'websiteId' => $value['website_id'],
                'priceListId' => $value['price_list_id']
            ];

            $this->saveData($logger, $sql, $params, $types, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @return array
     */
    protected function fetchData(LoggerInterface $logger, $sql)
    {
        $logger->info($sql);
        return $this->connection->fetchAll($sql);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @param array $params
     * @param array $types
     * @param bool $dryRun
     */
    protected function saveData(LoggerInterface $logger, $sql, array $params, array $types, $dryRun)
    {
        $this->logQuery($logger, $sql, $params, $types);

        if (!$dryRun) {
            $this->connection->executeQuery($sql, $params, $types);
        }
    }

    /**
     * @param array $oldPriorities
     * @param string $firstLevel
     * @param string|null $secondLevel
     * @return array
     */
    protected function collectPriorities(array $oldPriorities, $firstLevel, $secondLevel = null)
    {
        $priorities = [];

        foreach ($oldPriorities as $value) {
            $firstLevelValue = $value[$firstLevel];

            if (empty($priorities[$firstLevelValue])) {
                $priorities[$firstLevelValue] = [];
            }

            if ($secondLevel !== null) {
                $secondLevelValue = $value[$secondLevel];
                if (empty($priorities[$firstLevelValue][$secondLevelValue])) {
                    $priorities[$firstLevelValue][$secondLevelValue] = [];
                }
                $priorities[$firstLevelValue][$secondLevelValue][] = $value['priority'];
            } else {
                $priorities[$firstLevelValue][] = $value['priority'];
            }
        }

        return $priorities;
    }
}
