<?php

namespace Oro\Bundle\AccountBundle\Datagrid\Extension;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class AccountUserByAccountExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'account-account-user-select-grid';
    const ACCOUNT_KEY = 'account_id';

    /**
     * @var bool
     */
    protected $applied = false;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (!$this->isApplicable($config) || !$datasource instanceof OrmDatasource) {
            return;
        }

        $accountId = $this->requestStack->getCurrentRequest()->get(self::ACCOUNT_KEY);

        /** @var OrmDatasource $datasource */
        $qb = $datasource->getQueryBuilder();

        $rootAlias = $qb->getRootAliases()[0];
        $qb->andWhere($qb->expr()->eq(sprintf('IDENTITY(%s.account)', $rootAlias), ':account'))
            ->setParameter('account', $accountId);

        $this->applied = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $request = $this->requestStack->getCurrentRequest();

        return !$this->applied
            && static::SUPPORTED_GRID === $config->getName()
            && $request
            && $request->get(self::ACCOUNT_KEY);
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
