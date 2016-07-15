<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridHelper;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
     * @var BaseCheckoutRepository
     */
    protected $baseCheckoutRepository;
    
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
     * @param UserCurrencyManager    $currencyManager
     * @param BaseCheckoutRepository $baseCheckoutRepository
     * @param TotalProcessorProvider $totalProcessor
     * @param EntityNameResolver     $entityNameResolver
     * @param Cache                  $cache
     * @param CheckoutGridHelper     $checkoutGridHelper
     * @internal param Getter $getter
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        BaseCheckoutRepository $baseCheckoutRepository,
        TotalProcessorProvider $totalProcessor,
        EntityNameResolver $entityNameResolver,
        Cache $cache,
        CheckoutGridHelper $checkoutGridHelper
    ) {
        $this->currencyManager        = $currencyManager;
        $this->baseCheckoutRepository = $baseCheckoutRepository;
        $this->totalProcessor         = $totalProcessor;
        $this->entityNameResolver     = $entityNameResolver;
        $this->cache                  = $cache;
        $this->checkoutGridHelper     = $checkoutGridHelper;
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
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
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

        $counts = $this->baseCheckoutRepository->countItemsPerCheckout($ids);

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

        $sources = $this->baseCheckoutRepository->getSourcePerCheckout($ids);

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
        $em = $this->baseCheckoutRepository;

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
