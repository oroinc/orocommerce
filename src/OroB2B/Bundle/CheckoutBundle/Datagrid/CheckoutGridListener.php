<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Add total and subtotal fields to grid where root entity is checkout
 * Total field consider to be first non empty field among source entities and marked as is_total in field config
 * Subtotal field consider to be first non empty field among source entities and marked as is_subtotal in field config
 */
class CheckoutGridListener
{
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
     * @param ConfigProvider $configProvider
     * @param EntityFieldProvider $fieldProvider
     */
    public function __construct(
        ConfigProvider $configProvider,
        EntityFieldProvider $fieldProvider
    ) {
        $this->configProvider = $configProvider;
        $this->fieldProvider = $fieldProvider;
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
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getGridUpdates(DatagridConfiguration $config)
    {
        $totalMetadata = $this->getSourceTotalsMetadata();

        $updates = [];
        if ($totalMetadata) {
            $from = $config->offsetGetByPath('[source][query][from]');
            $firstFrom = current($from);
            $updates['joins'][] = ['join' => $firstFrom['alias'] . '.source', 'alias' => '_source'];

            $totalFields = [];
            $subtotalFields = [];
            $currencyFields = [];
            foreach ($totalMetadata as $field => $metadata) {
                $totalHolderAlias = '_' . $field;
                $updates['joins'][] = ['join' => '_source.' . $field, 'alias' => $totalHolderAlias];

                if (!empty($metadata['total'])) {
                    $totalFields[] = $totalHolderAlias . '.' . $metadata['total'];
                }
                if (!empty($metadata['subtotal'])) {
                    $subtotalFields[] = $totalHolderAlias . '.' . $metadata['subtotal'];
                }
                if (!empty($metadata['currency'])) {
                    $currencyFields[] = $totalHolderAlias . '.' . $metadata['currency'];
                }
            }

            if ($totalFields) {
                $updates['selects'][] = sprintf('COALESCE(%s) as total', implode(',', $totalFields));
                $updates['columns']['total'] = [
                    'label' => 'orob2b.checkout.grid.total.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                ];

                $updates['filters']['total'] = [
                    'type' => 'number',
                    'data_name' => 'total'
                ];
                $updates['sorters']['total'] = ['data_name' => 'total'];
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
        $relationsMetadata = $this->fieldProvider->getRelations(
            'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource',
            false,
            false,
            false
        );

        if (!$relationsMetadata) {
            return $metadata;
        }

        foreach ($relationsMetadata as $relationMetadata) {
            if (!empty($relationMetadata['related_entity_name'])) {
                $relatedEntityName = $relationMetadata['related_entity_name'];
                $relationField = $relationMetadata['name'];

                if ($totalsMetadata = $this->getTotalsMetadata($relatedEntityName)) {
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
        $metadata = [];
        $fields = $this->fieldProvider->getFields($entityName);
        if (!$fields) {
            return $metadata;
        }
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->configProvider->hasConfig($entityName, $fieldName)) {
                $fieldConfig = $this->configProvider->getConfig($entityName, $fieldName);
                if ($fieldConfig->get('is_total')) {
                    $metadata['total'] = $fieldName;
                }
                if ($fieldConfig->get('is_subtotal')) {
                    $metadata['subtotal'] = $fieldName;
                }
                if ($fieldConfig->get('is_total_currency')) {
                    $metadata['currency'] = $fieldName;
                }
            }
        }

        return $metadata;
    }
}
