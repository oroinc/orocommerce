<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\Updater;

use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;

class Getter
{
    const TYPE_JOIN_COLLECTION = 'join_collection';
    const TYPE_ENTITY_FIELDS   = 'entity_fields';

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param RegistryInterface   $doctrine
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProvider      $configProvider
     */
    public function __construct(
        RegistryInterface $doctrine,
        EntityFieldProvider $fieldProvider,
        ConfigProvider $configProvider
    ) {
        $this->doctrine       = $doctrine;
        $this->fieldProvider  = $fieldProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    public function getUpdates(DatagridConfiguration $config)
    {
        $totalMetadata = $this->getSourceTotalsMetadata();
        $updates       = [
            'selects'        => [],
            'columns'        => [],
            'filters'        => [],
            'sorters'        => [],
            'joins'          => [],
            'bindParameters' => []
        ];

        if ($totalMetadata) {
            $from               = $config->offsetGetByPath('[source][query][from]');
            $firstFrom          = current($from);
            $updates['joins'][] = ['join' => $firstFrom['alias'] . '.source', 'alias' => '_source'];

            list(
                $totalFields,
                $subtotalFields,
                $currencyFields,
                $updates) = $this->processMetadata($totalMetadata, $updates);

            if ($subtotalFields) {
                $updates['selects'][]           = sprintf('COALESCE(%s) as subtotal', implode(',', $subtotalFields));
                $updates['columns']['subtotal'] = [
                    'label'         => 'orob2b.checkout.grid.subtotal.label',
                    'type'          => 'twig',
                    'frontend_type' => 'html',
                    'template'      => 'OroB2BPricingBundle:Datagrid:Column/subtotal.html.twig',
                    'order'         => 25
                ];
                $updates['filters']['subtotal'] = [
                    'type'      => 'number',
                    'data_name' => 'subtotal'
                ];

                $updates['sorters']['subtotal'] = ['data_name' => 'subtotal'];
            }
            if ($totalFields) {
                $updates['selects'][]        = sprintf('COALESCE(%s) as total', implode(',', $totalFields));
                $updates['columns']['total'] = [
                    'label'         => 'orob2b.checkout.grid.total.label',
                    'type'          => 'twig',
                    'frontend_type' => 'html',
                    'template'      => 'OroB2BPricingBundle:Datagrid:Column/total.html.twig',
                    'order'         => 85,
                    'renderable'    => false
                ];
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
        $metadata          = [];
        $relationsMetadata = $this->fieldProvider->getRelations(CheckoutSource::class, false, false, false);

        if (!$relationsMetadata) {
            return $metadata;
        }

        foreach ($relationsMetadata as $relationMetadata) {
            if (!empty($relationMetadata['related_entity_name'])) {
                $relatedEntityName = $relationMetadata['related_entity_name'];
                $relationField     = $relationMetadata['name'];

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
        $totalFields    = [];
        $subtotalFields = [];
        $currencyFields = [];

        foreach ($totalMetadata as $field => $metadata) {
            $totalHolderAlias   = '_' . $field;
            $updates['joins'][] = ['join' => '_source.' . $field, 'alias' => $totalHolderAlias];

            if ($metadata['type'] === self::TYPE_JOIN_COLLECTION) {
                $relationAlias      = $totalHolderAlias . '_' . $metadata['join_field'];
                $fields             = $metadata['relation_fields'];
                $updates['joins'][] = [
                    'join'          => $totalHolderAlias . '.' . $metadata['join_field'],
                    'alias'         => $relationAlias,
                    'conditionType' => 'WITH',
                    'condition'     => sprintf(
                        '%s = :' . CheckoutGridListener::USER_CURRENCY_PARAMETER,
                        $relationAlias . '.' . $fields['currency']
                    )
                ];
                if (!in_array(CheckoutGridListener::USER_CURRENCY_PARAMETER, $updates['bindParameters'], true)) {
                    $updates['bindParameters'][] = CheckoutGridListener::USER_CURRENCY_PARAMETER;
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
     * @param array  $metadata
     * @param string $entityName
     * @return array
     * @throws IncorrectEntityException
     */
    public function resolveMetadata(array $metadata, $entityName)
    {
        $metadata       = $this->getMetadataOptionsResolver()->resolve($metadata);
        $em             = $this->doctrine->getManagerForClass($entityName);
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
            $relationName                = $entityMetadata->getAssociationTargetClass($metadata['join_field']);
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
                                           'join_field'      => '',
                                           'relation_fields' => [],
                                           'fields'          => []
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
                                         'total'    => ''
                                     ]);

        return $fieldsResolver;
    }

    /**
     * @param array         $fields
     * @param ClassMetadata $relationMetadata
     * @param string        $relationName
     * @throws IncorrectEntityException
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
