<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
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
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param UserCurrencyManager $currencyManager
     * @param CheckoutRepository $checkoutRepository
     * @param TotalProcessorProvider $totalProcessor
     * @param EntityNameResolver $entityNameResolver
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        CheckoutRepository $checkoutRepository,
        TotalProcessorProvider $totalProcessor,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper
    ) {
        $this->currencyManager = $currencyManager;
        $this->checkoutRepository = $checkoutRepository;
        $this->totalProcessor = $totalProcessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrineHelper = $doctrineHelper;
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
        $checkouts = $this->checkoutRepository->getCheckoutsByIds($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');

            /** @var Checkout $ch */
            $ch = $this->checkoutRepository->find($id);
            $data = $ch->getCompletedData();

            if (isset($checkouts[$id])) {
                $sourceEntity = $checkouts[$id]->getSource()->getEntity();

                if ($sourceEntity) {
                    $record->addData(['startedFrom' => $this->getStartedFrom($sourceEntity)]);
                }
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

            if (isset($checkouts[$id]) && !$record->getValue('isSubtotalValid')) {
                $this->updateTotal($checkouts[$id], $record);
            }
        }
    }

    /**
     * @param object $checkout
     * @param ResultRecord $record
     */
    protected function updateTotal($checkout, ResultRecord $record)
    {
        $subtotal = $this->totalProcessor->getTotal($checkout);
        $record->setValue('subtotal', $subtotal->getAmount());
        $record->setValue('total', $record->getValue('subtotal') + $record->getValue('shippingEstimateAmount'));
        $record->setValue('currency', $subtotal->getCurrency());
    }

    /**
     * @param CheckoutSourceEntityInterface $source
     * @return array
     */
    protected function getStartedFrom(CheckoutSourceEntityInterface $source)
    {
        $sourceEntity = $source->getSourceDocument();

        // simplify type checking in twig
        $type = $this->getShortClassName($sourceEntity);

        return [
            'entity' => $source,
            'type' => $type,
            'label' => $this->entityNameResolver->getName($sourceEntity),
            'id' => $this->doctrineHelper->getSingleEntityIdentifier($sourceEntity)
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

    /**
     * @param object $object
     * @return string
     */
    protected function getShortClassName($object)
    {
        return Inflector::tableize(ExtendHelper::getShortClassName(ClassUtils::getClass($object)));
    }
}
