<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
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
     * @var EntityNameResolver
     */
    private $entityNameResolver;

    /**
     * @param UserCurrencyManager $currencyManager
     * @param CheckoutRepository $checkoutRepository
     * @param TotalProcessorProvider $totalProcessor
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        CheckoutRepository $checkoutRepository,
        TotalProcessorProvider $totalProcessor,
        EntityNameResolver $entityNameResolver
    ) {
        $this->currencyManager = $currencyManager;
        $this->checkoutRepository = $checkoutRepository;
        $this->totalProcessor = $totalProcessor;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath(
            DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH,
            [self::USER_CURRENCY_PARAMETER]
        );
        $event->getDatagrid()
              ->getParameters()
              ->set(self::USER_CURRENCY_PARAMETER, $this->currencyManager->getUserCurrency());
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $dataSource = $event->getDatagrid()->getDatasource();
        if (!$dataSource instanceof OrmDatasource) {
            return;
        }

        $dataGridQueryBuilder = $dataSource->getQueryBuilder();
        $aliases = $dataGridQueryBuilder->getRootAliases();
        $alias = reset($aliases);
        $dataGridQueryBuilder->andWhere($dataGridQueryBuilder->expr()->eq($alias . '.completed', ':completed'));
        $dataGridQueryBuilder->setParameter('completed', false, Type::BOOLEAN);
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
        $sources = $this->checkoutRepository->getCheckoutsByIds($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');

            /** @var Checkout $ch */
            $ch = $this->checkoutRepository->find($id);
            $data = $ch->getCompletedData();

            if (isset($sources[$id])) {
                $record->addData(['startedFrom' => $this->getStartedFrom($sources[$id]->getSource()->getEntity())]);
            }

            if ($record->getValue('completed')) {
                if (!$record->getValue('startedFrom')) {
                    $this->addCompletedCheckoutData($record, $data, ['startedFrom']);
                }

                $this->addCompletedCheckoutData($record, $data, ['itemsCount', 'currency']);
                continue;
            }

            if (isset($counts[$id])) {
                $record->addData(['itemsCount' => $counts[$id]]);
            }

            if (isset($sources[$id]) && !$record->getValue('is_subtotal_valid')) {
                $this->updateTotal($sources[$id], $record);
            }
        }
    }

    /**
     * @param object $entity
     * @param ResultRecord $record
     */
    protected function updateTotal($entity, ResultRecord $record)
    {
        $subtotal = $this->totalProcessor->getTotal($entity);
        $record->setValue('subtotal', $subtotal->getAmount());
        $record->setValue('total', $record->getValue('subtotal') + $record->getValue('shippingEstimateAmount'));
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
            $method = 'get' . ucfirst($key);
            $value = $data->$method();

            if ($value) {
                $record->addData([$key => $value]);
            }
        }
    }
}
