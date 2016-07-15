<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Cache\Cache;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Add total and subtotal fields to grid where root entity is checkout
 */
class CheckoutGridListener
{
    const TYPE_JOIN_COLLECTION = 'join_collection';
    const TYPE_ENTITY_FIELDS = 'entity_fields';
    const USER_CURRENCY_PARAMETER = 'user_currency';
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessor;

    /**
     * @param ConfigProvider $configProvider
     * @param EntityFieldProvider $fieldProvider
     * @param RegistryInterface $doctrine
     * @param UserCurrencyManager $currencyManager
     * @param TotalProcessorProvider $totalProcessor
     */
    public function __construct(
        ConfigProvider $configProvider,
        EntityFieldProvider $fieldProvider,
        RegistryInterface $doctrine,
        UserCurrencyManager $currencyManager,
        TotalProcessorProvider $totalProcessor
    ) {
        $this->configProvider = $configProvider;
        $this->fieldProvider = $fieldProvider;
        $this->doctrine = $doctrine;
        $this->currencyManager = $currencyManager;
        $this->totalProcessor = $totalProcessor;
    }

    /**
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        if ($this->cache && $this->cache->contains($config->getName())) {
            $updates = $this->cache->fetch($config->getName());
        } else {
            $updates = $this->getGridUpdates($config);

            if ($this->cache) {
                $this->cache->save($config->getName(), $updates);
            }
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
        $em = $this->doctrine->getManagerForClass(BaseCheckout::class);
        // todo: BB-3822 Reduce db queries count
        foreach ($records as $record) {
            if (!$record->getValue('total')) {
                $id = $record->getValue('id');
                $ch = $em->find(BaseCheckout::class, $id);
                $record->addData(['total' => $this->totalProcessor->getTotal($ch->getSourceEntity())->getAmount()]);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getGridUpdates(DatagridConfiguration $config)
    {
        $totalMetadata = $this->getSourceTotalsMetadata();
        $updates = [
            'selects' => [],
            'columns' => [],
            'filters' => [],
            'sorters' => [],
            'joins' => [],
            'bindParameters' => []
        ];

        if ($totalMetadata) {
            $from = $config->offsetGetByPath('[source][query][from]');
            $firstFrom = current($from);
            $updates['joins'][] = ['join' => $firstFrom['alias'] . '.source', 'alias' => '_source'];

            list(
                $totalFields,
                $subtotalFields,
                $currencyFields,
                $updates) = $this->processMetadata($totalMetadata, $updates);

            if ($totalFields) {
                $updates['selects'][] = sprintf('COALESCE(%s) as total', implode(',', $totalFields));
                $updates['columns']['total'] = [
                    'label' => 'orob2b.checkout.grid.total.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                ];
            }
            if ($subtotalFields) {
                $updates['selects'][] = sprintf('COALESCE(%s) as subtotal', implode(',', $subtotalFields));
                $updates['columns']['subtotal'] = [
                    'label' => 'orob2b.checkout.grid.subtotal.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'template' => 'OroB2BPricingBundle:Datagrid:Column/subtotal.html.twig',
                ];
                $updates['filters']['subtotal'] = [
                    'type' => 'number',
                    'data_name' => 'subtotal'
                ];

                $updates['sorters']['subtotal'] = ['data_name' => 'subtotal'];
            }
            if ($currencyFields) {
                $updates['selects'][] = sprintf('COALESCE(%s) as currency', implode(',', $currencyFields));
            }
        }

        return $updates;
    }

    /**
     * @return array
     */
    protected function getSourceTotalsMetadata()
    {
        $metadata = [];
        $relationsMetadata = $this->fieldProvider->getRelations(CheckoutSource::class, false, false, false);

        if (!$relationsMetadata) {
            return $metadata;
        }

        foreach ($relationsMetadata as $relationMetadata) {
            if (!empty($relationMetadata['related_entity_name'])) {
                $relatedEntityName = $relationMetadata['related_entity_name'];
                $relationField = $relationMetadata['name'];

                $totalsMetadata = $this->getTotalsMetadata($relatedEntityName);
                if ($totalsMetadata) {
                    $metadata[$relationField] = $totalsMetadata;
                }
            }
        }

        return $metadata;
    }

    /**
     * @param string $entityName
     * @return array
     */
    protected function getTotalsMetadata($entityName)
    {
        $entityConfig = $this->configProvider->getConfig($entityName);
        if ($entityConfig->has('totals_mapping')) {
            return $this->resolveMetadata($entityConfig->get('totals_mapping'), $entityName);
        }

        return [];
    }

    /**
     * @param array $totalMetadata
     * @param array $updates
     * @return array
     */
    protected function processMetadata(array $totalMetadata, array $updates)
    {
        $totalFields = [];
        $subtotalFields = [];
        $currencyFields = [];

        foreach ($totalMetadata as $field => $metadata) {
            $totalHolderAlias = '_' . $field;
            $updates['joins'][] = ['join' => '_source.' . $field, 'alias' => $totalHolderAlias];

            if ($metadata['type'] === self::TYPE_JOIN_COLLECTION) {
                $relationAlias = $totalHolderAlias . '_' . $metadata['join_field'];
                $fields = $metadata['relation_fields'];
                $updates['joins'][] = [
                    'join' => $totalHolderAlias . '.' . $metadata['join_field'],
                    'alias' => $relationAlias,
                    'conditionType' => 'WITH',
                    'condition' => sprintf(
                        '%s = :'.self::USER_CURRENCY_PARAMETER,
                        $relationAlias . '.' . $fields['currency']
                    )
                ];
                if (!in_array(self::USER_CURRENCY_PARAMETER, $updates['bindParameters'], true)) {
                    $updates['bindParameters'][] = self::USER_CURRENCY_PARAMETER;
                }
                $currencyFields[] = $relationAlias . '.' . $fields['currency'];
                if ($fields['subtotal']) {
                    $subtotalFields[] = $relationAlias . '.' . $fields['subtotal'];
                }
            }

            if ($metadata['type'] === self::TYPE_ENTITY_FIELDS) {
                $fields = $metadata['fields'];
                if ($fields['total']) {
                    $totalFields[] = $totalHolderAlias . '.' . $fields['total'];
                }
                if ($fields['subtotal']) {
                    $subtotalFields[] = $totalHolderAlias . '.' . $fields['subtotal'];
                }
                if ($fields['currency']) {
                    $currencyFields[] = $totalHolderAlias . '.' . $fields['currency'];
                }
            }
        }
        return [$totalFields, $subtotalFields, $currencyFields, $updates];
    }

    /**
     * @param array $metadata
     * @param string $entityName
     * @return array
     */
    public function resolveMetadata(array $metadata, $entityName)
    {
        $metadata = $this->getMetadataOptionsResolver()->resolve($metadata);
        $em = $this->doctrine->getManagerForClass($entityName);
        $entityMetadata = $em->getClassMetadata($entityName);
        $fieldsResolver = $this->getFieldsOptionsResolver();

        if ($metadata['type'] === self::TYPE_ENTITY_FIELDS) {
            $metadata['fields'] = $fieldsResolver->resolve($metadata['fields']);
            $this->checkFieldsExists($metadata['fields'], $entityMetadata, $entityName);
        }

        if ($metadata['type'] === self::TYPE_JOIN_COLLECTION) {
            if (!$entityMetadata->hasAssociation($metadata['join_field'])) {
                throw new IncorrectEntityException(
                    sprintf("Entity '%s' doesn't have association '%s'", $entityName, $metadata['join_field'])
                );
            }

            $metadata['relation_fields'] = $fieldsResolver->resolve($metadata['relation_fields']);
            $relationName = $entityMetadata->getAssociationTargetClass($metadata['join_field']);
            $this->checkFieldsExists($metadata['relation_fields'], $em->getClassMetadata($relationName), $relationName);
        }

        return $metadata;
    }

    /**
     * @return OptionsResolver
     */
    protected function getMetadataOptionsResolver()
    {
        $metadataResolver = new OptionsResolver();
        $metadataResolver->setRequired('type')
            ->setAllowedValues('type', [self::TYPE_JOIN_COLLECTION, self::TYPE_ENTITY_FIELDS])
            ->setDefaults([
                'join_field' => '',
                'relation_fields' => [],
                'fields' => []
            ])
            ->setAllowedTypes('join_field', 'string')
            ->setAllowedTypes('relation_fields', 'array')
            ->setAllowedTypes('fields', 'array');

        return $metadataResolver;
    }

    /**
     * @return OptionsResolver
     */
    protected function getFieldsOptionsResolver()
    {
        $fieldsResolver = new OptionsResolver();
        $fieldsResolver->setDefaults([
            'currency' => '',
            'subtotal' => '',
            'total' => ''
        ]);

        return $fieldsResolver;
    }

    /**
     * @param array $fields
     * @param ClassMetadata $relationMetadata
     * @param string $relationName
     */
    protected function checkFieldsExists(array $fields, ClassMetadata $relationMetadata, $relationName)
    {
        foreach ($fields as $field) {
            if ($field && !$relationMetadata->hasField($field)) {
                throw new IncorrectEntityException(
                    sprintf("Entity '%s' doesn't have field '%s'", $relationName, $field)
                );
            }
        }
    }
}
