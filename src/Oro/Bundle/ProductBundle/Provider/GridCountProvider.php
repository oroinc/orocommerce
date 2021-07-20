<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Get number of rows in submitted grid without filters
 */
class GridCountProvider
{
    /**
     * @var ManagerInterface
     */
    private $gridManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var Pager
     */
    private $pager;

    public function __construct(
        ManagerInterface $gridManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Pager $pager
    ) {
        $this->gridManager = $gridManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->pager = $pager;
    }

    /**
     * @param string $gridName
     * @param array $params
     * @return int
     */
    public function getGridCount($gridName, array $params = []): int
    {
        $result = 0;
        $this->checkAcl($gridName);

        $dataSource = $this->getDataSource($gridName, $params);
        if ($dataSource instanceof OrmDatasource) {
            $this->pager->setDatagrid($dataSource->getDatagrid());
            $this->pager->setQueryBuilder($dataSource->getQueryBuilder());
            $countQb = $dataSource->getCountQb();
            if ($countQb) {
                $this->pager->setCountQb($countQb, $dataSource->getCountQueryHints());
            }

            $result = (int)$this->pager->computeNbResult();
        }

        return $result;
    }

    /**
     * @param string $gridName
     * @throws AccessDeniedException
     */
    private function checkAcl($gridName)
    {
        $gridConfig = $this->gridManager->getConfigurationForGrid($gridName);
        $acl = $gridConfig->getAclResource();
        if ($acl && !$this->authorizationChecker->isGranted($acl)) {
            throw new AccessDeniedException('Access denied.');
        }
    }

    /**
     * @param string $gridName
     * @param array $params
     * @return DatasourceInterface
     */
    private function getDataSource($gridName, array $params = []): DatasourceInterface
    {
        $grid = $this->gridManager->getDatagridByRequestParams($gridName, $params);
        $grid->getParameters()->set(AbstractFilterExtension::FILTER_ROOT_PARAM, []);

        return $grid->getAcceptedDatasource();
    }
}
