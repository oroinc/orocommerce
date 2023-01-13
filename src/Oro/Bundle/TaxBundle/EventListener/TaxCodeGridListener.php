<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Adds taxCode field to a grid.
 */
class TaxCodeGridListener
{
    private string $taxCodeClass;
    private FeatureChecker $featureChecker;

    public function __construct(string $taxCodeClass, FeatureChecker $featureChecker)
    {
        $this->taxCodeClass = $taxCodeClass;
        $this->featureChecker = $featureChecker;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->featureChecker->isResourceEnabled($this->getTaxCodeClass(), 'entities')) {
            return;
        }

        $config = $event->getConfig();
        $this->addSelect($config);
        $this->addJoin($config);
        $this->addColumn($config);
        $this->addSorter($config);
        $this->addFilter($config);
    }

    protected function getAlias(DatagridConfiguration $configuration): string
    {
        $rootAlias = $configuration->getOrmQuery()->getRootAlias();
        if (!$rootAlias) {
            throw new \InvalidArgumentException(sprintf(
                'A root entity is missing for grid "%s"',
                $configuration->getName()
            ));
        }

        return $rootAlias;
    }

    protected function getColumnLabel(): string
    {
        return 'oro.tax.taxcode.label';
    }

    protected function getDataName(): string
    {
        return 'taxCode';
    }

    protected function getJoinAlias(): string
    {
        return 'taxCodes';
    }

    protected function getTaxCodeClass(): string
    {
        return $this->taxCodeClass;
    }

    protected function getTaxCodeField(): string
    {
        return 'taxCode';
    }

    protected function addSelect(DatagridConfiguration $config): void
    {
        $config->getOrmQuery()->addSelect(
            sprintf('%s.code AS %s', $this->getJoinAlias(), $this->getDataName())
        );
    }

    protected function addJoin(DatagridConfiguration $config): void
    {
        $config->getOrmQuery()->addLeftJoin(
            $this->getAlias($config) . '.' . $this->getTaxCodeField(),
            $this->getJoinAlias()
        );
    }

    protected function addColumn(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataName()),
            ['label' => $this->getColumnLabel()]
        );
    }

    protected function addSorter(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataName()),
            ['data_name' => $this->getDataName()]
        );
    }

    protected function addFilter(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataName()),
            [
                'type' => 'entity',
                'data_name' => $this->getAlias($config) . '.' . $this->getTaxCodeField(),
                'options' => [
                    'field_options' => [
                        'multiple' => false,
                        'class' => $this->getTaxCodeClass(),
                        'choice_label' => 'code'
                    ]
                ]
            ]
        );
    }
}
