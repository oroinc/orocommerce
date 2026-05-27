<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * Disables the order_draft ORM filter for the order line items edit datagrid.
 */
class OrderLineItemsDraftFilterExtension extends AbstractExtension
{
    private string $datagridName = 'order-line-items-edit-grid';

    private bool $isOrmFilterEnabled = true;

    public function __construct(
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager
    ) {
    }

    public function setDatagridName(string $datagridName): void
    {
        $this->datagridName = $datagridName;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return $config->getName() === $this->datagridName;
    }

    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource): void
    {
        $this->isOrmFilterEnabled = $this->draftSessionOrmFilterManager->isEnabled();
        if ($datasource instanceof OrmDatasource) {
            $this->draftSessionOrmFilterManager->disable();
        }
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        if ($this->isOrmFilterEnabled) {
            $this->draftSessionOrmFilterManager->enable();
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        // High priority to run before other extensions
        return 300;
    }
}
