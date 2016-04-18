<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

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
     * {@inheritdoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        
        $totalMetadata = $this->getCheckoutSources();

        if ($totalMetadata) {
            $from = $config->offsetGetByPath('[source][query][from]');
            if ($from) {
                $leftJoins = $config->offsetGetByPath('[source][query][join][left]', array());
                $firstFrom = current($from);
                if (!empty($firstFrom['table']) && !empty($firstFrom['alias'])) {
                    $rootEntityAlias = $firstFrom['alias'];

                    $leftJoins[] = ['join' => $rootEntityAlias . '.source', 'alias' => '_source'];

                    $totalFields = [];
                    $subtotalFields = [];
                    $currencyFields = [];
                    foreach ($totalMetadata as $field => $metadata) {
                        $totalHolderAlias = '_' . $field;
                        $leftJoins[] = ['join' =>  '_source.' . $field, 'alias' => $totalHolderAlias];

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

                    $selects = $config->offsetGetByPath('[source][query][select]', []);
                    $columns = $config->offsetGetByPath('[columns]', []);
                    $filters = $config->offsetGetByPath('[filters][columns]', []);
                    $sorters = $config->offsetGetByPath('[sorters][columns]', []);
                    if ($totalFields) {
                        $selects[] = sprintf('COALESCE(%s) as total', implode(',', $totalFields));
                        $columns['total'] = [
                            'label' => 'TOTAL',
                            'type' => 'twig',
                            'frontend_type' => 'html',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                            'renderable' => false
                        ];
                        if ($filters) {
                            $filters['total'] = [
                                'type' => 'number',
                                'data_name' => 'total'
                            ];
                        }
                        if ($sorters) {
                            $sorters['total'] = ['data_name' => 'total'];
                        }
                    }
                    if ($subtotalFields) {
                        $selects[] = sprintf('COALESCE(%s) as subtotal', implode(',', $subtotalFields));
                        $columns['subtotal'] = [
                            'label' => 'SUBTOTAL',
                            'type' => 'twig',
                            'frontend_type' => 'html',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/subtotal.html.twig',
                            'renderable' => false
                        ];
                        if ($filters) {
                            $filters['subtotal'] = [
                                'type' => 'number',
                                'data_name' => 'subtotal',
                                'enabled' => false
                            ];
                        }
                        if ($sorters) {
                            $sorters['subtotal'] = ['data_name' => 'subtotal'];
                        }
                    }
                    if ($currencyFields) {
                        $selects[] = sprintf('COALESCE(%s) as totalsCurrency', implode(',', $currencyFields));
                    }

                    $config->offsetSetByPath('[source][query][select]', $selects);
                    $config->offsetSetByPath('[columns]', $columns);
                    $config->offsetSetByPath('[filters][columns]', $filters);
                    $config->offsetSetByPath('[sorters][columns]', $sorters);
                }

                $config->offsetSetByPath('[source][query][join][left]', $leftJoins);
            }
        }
    }

    /**
     * @return array
     */
    protected function getCheckoutSources()
    {
        $metadata = [];
        $relationsMetadata = $this->fieldProvider->getRelations(
            'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource',
            false,
            false,
            false
        );
        foreach ($relationsMetadata as $relationMetadata) {
            if (!empty($relationMetadata['related_entity_name'])) {
                $relatedEntityName = $relationMetadata['related_entity_name'];
                $relationField = $relationMetadata['name'];

                $fields = $this->fieldProvider->getFields($relatedEntityName);
                foreach ($fields as $field) {
                    $fieldName = $field['name'];
                    if ($this->configProvider->hasConfig($relatedEntityName, $fieldName)) {
                        $fieldConfig = $this->configProvider->getConfig($relatedEntityName, $fieldName);
                        if ($fieldConfig->get('is_total')) {
                            $metadata[$relationField]['total'] = $fieldName;
                        }
                        if ($fieldConfig->get('is_subtotal')) {
                            $metadata[$relationField]['subtotal'] = $fieldName;
                        }
                        if ($fieldConfig->get('is_total_currency')) {
                            $metadata[$relationField]['currency'] = $fieldName;
                        }
                    }
                }
            }
        }

        return $metadata;
    }
}
