<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Add total and subtotal fields to grid where root entity is checkout
 */
class CheckoutGridListener
{
    const USER_CURRENCY_PARAMETER = 'user_currency';

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var CheckoutRepository
     */
    protected $checkoutRepository;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessor;

    /**
     * @var CheckoutGridHelper
     */
    protected $checkoutGridHelper;

    /**
     * @var EntityNameResolver
     */
    private $entityNameResolver;

    /**
     * @param UserCurrencyManager $currencyManager
     * @param CheckoutRepository $checkoutRepository
     * @param TotalProcessorProvider $totalProcessor
     * @param EntityNameResolver $entityNameResolver
     * @param Cache $cache
     * @param CheckoutGridHelper $checkoutGridHelper
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        CheckoutRepository $checkoutRepository,
        TotalProcessorProvider $totalProcessor,
        EntityNameResolver $entityNameResolver,
        Cache $cache,
        CheckoutGridHelper $checkoutGridHelper
    ) {
        $this->currencyManager = $currencyManager;
        $this->checkoutRepository = $checkoutRepository;
        $this->totalProcessor = $totalProcessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->cache = $cache;
        $this->checkoutGridHelper = $checkoutGridHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $key    = $config->getName();

        if ($this->cache->contains($key)) {
            $updates = $this->cache->fetch($key);
        } else {
            $updates = $this->checkoutGridHelper->getUpdates($config);
            $this->cache->save($key, $updates);
        }

        if ($updates) {
            $query = $config->getOrmQuery();
            $query->addSelect($updates['selects']);
            $query->setLeftJoins(array_merge($query->getLeftJoins(), $updates['joins']));
            $config->offsetAddToArrayByPath('[columns]', $updates['columns']);
            $config->offsetAddToArrayByPath('[filters][columns]', $updates['filters']);
            $config->offsetAddToArrayByPath('[sorters][columns]', $updates['sorters']);
            $config->offsetAddToArrayByPath(
                DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH,
                $updates['bindParameters']
            );

            if (in_array(self::USER_CURRENCY_PARAMETER, $updates['bindParameters'], true)) {
                $event->getDatagrid()
                      ->getParameters()
                      ->set(self::USER_CURRENCY_PARAMETER, $this->currencyManager->getUserCurrency());
            }
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $this->buildColumns($records);
    }

    /**
     * @param ResultRecord[] $records
     */
    protected function buildColumns(array $records)
    {
        $ids = array_map(
            function (ResultRecord $record) {
                return $record->getValue('id');
            },
            $records
        );

        $counts = $this->checkoutRepository->countItemsPerCheckout($ids);
        $sources = $this->checkoutRepository->getSourcePerCheckout($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');

            /** @var Checkout $ch */
            $ch = $this->checkoutRepository->find($id);
            $data = $ch->getCompletedData();

            if (isset($sources[$id])) {
                $record->addData(['startedFrom' => $this->getStartedFrom($sources[$id])]);
            }

            if ($record->getValue('completed')) {
                if (!$record->getValue('startedFrom')) {
                    $this->addCompletedCheckoutData($record, $data, ['startedFrom']);
                }

                $this->addCompletedCheckoutData($record, $data, ['itemsCount', 'total', 'subtotal', 'currency']);
                continue;
            }

            if (isset($counts[$id])) {
                $record->addData(['itemsCount' => $counts[$id]]);
            }

            if (isset($sources[$id]) && !$record->getValue('total')) {
                $record->addData(['total' => $this->totalProcessor->getTotal($sources[$id])->getAmount()]);
            }
        }
    }

    /**
     * @param CheckoutSourceEntityInterface $source
     * @return array
     */
    protected function getStartedFrom(CheckoutSourceEntityInterface $source)
    {
        if ($source instanceof QuoteDemand) {
            $source = $source->getQuote();
        }

        $type = null;
        // simplify type checking in twig
        if ($source instanceof ShoppingList) {
            $type = 'shopping_list';
        }
        if ($source instanceof Quote) {
            $type = 'quote';
        }

        return [
            'entity' => $source,
            'type' => $type,
            'label' => $this->entityNameResolver->getName($source),
            'id' => $source->getId()
        ];
    }

    /**
     * @param ResultRecord $record
     * @param CompletedCheckoutData $data
     * @param array $keys
     */
    protected function addCompletedCheckoutData(ResultRecord $record, CompletedCheckoutData $data, array $keys)
    {
        foreach ($keys as $key) {
            if ($record->getValue($key)) {
                continue;
            }

            $method = 'get' . ucfirst($key);
            $value = $data->$method();

            if ($value) {
                $record->addData([$key => $value]);
            }
        }
    }
}
