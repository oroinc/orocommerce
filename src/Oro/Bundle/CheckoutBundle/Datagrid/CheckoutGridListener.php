<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\GridResultAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
            $config->offsetAddToArrayByPath('[source][query][select]', $updates['selects']);
            $config->offsetAddToArrayByPath('[columns]', $updates['columns']);
            $config->offsetAddToArrayByPath('[filters][columns]', $updates['filters']);
            $config->offsetAddToArrayByPath('[sorters][columns]', $updates['sorters']);
            $config->offsetAddToArrayByPath('[source][query][join][left]', $updates['joins']);
            $config->offsetAddToArrayByPath('[source][bind_parameters]', $updates['bindParameters']);

            if (in_array(self::USER_CURRENCY_PARAMETER, $updates['bindParameters'], true)) {
                $event->getDatagrid()
                      ->getParameters()
                      ->set(self::USER_CURRENCY_PARAMETER, $this->currencyManager->getUserCurrency());
            }
        }
    }

    /**
     * @param GridResultAfter $event
     */
    public function onResultAfter(GridResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $this->buildItemsCountColumn($records);
        $this->buildStartedFromColumn($records);
        $this->buildTotalColumn($records);
    }

    /**
     * @param ResultRecord[] $records
     */
    protected function buildItemsCountColumn($records)
    {
        $ids = [];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $counts = $this->checkoutRepository->countItemsPerCheckout($ids);

        foreach ($records as $record) {
            if (isset($counts[$record->getValue('id')])) {
                $record->addData(['itemsCount' => $counts[$record->getValue('id')]]);
            }
        }
    }

    /**
     * @param ResultRecord[] $records
     */
    protected function buildStartedFromColumn($records)
    {
        $ids = [];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $sources = $this->checkoutRepository->getSourcePerCheckout($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');
            if (!isset($sources[$id])) {
                continue;
            }

            $source = $sources[$id];

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

            $name = $this->entityNameResolver->getName($source);
            $data = [
                'entity' => $source,
                'type'   => $type,
                'label'  => $name,
                'id'     => $source->getId()
            ];

            $record->addData(['startedFrom' => $data]);
        }
    }

    /**
     * @param ResultRecord[] $records
     */
    protected function buildTotalColumn($records)
    {
        $em = $this->checkoutRepository;

        // todo: Reduce db queries count
        foreach ($records as $record) {
            if (!$record->getValue('total')) {
                $id = $record->getValue('id');
                $ch = $em->find($id);

                $sourceEntity = $ch->getSourceEntity();
                $record->addData(
                    [
                        'total' => $this->totalProcessor
                            ->getTotal($sourceEntity)
                            ->getAmount()
                    ]
                );
            }
        }
    }
}
