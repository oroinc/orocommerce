<?php

namespace Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The base class for datagrid mass actions for Coupon entity.
 */
abstract class AbstractCouponMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    public function __construct(
        DoctrineHelper $helper,
        AclHelper $aclHelper
    ) {
        $this->doctrineHelper = $helper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $datasource = $args->getDatagrid()->getDatasource();

        if (!$datasource instanceof OrmDatasource) {
            throw new UnexpectedTypeException($datasource, OrmDatasource::class);
        }

        $qb = clone $datasource->getQueryBuilder();
        if (!$args->getDatagrid()->getConfig()->isDatasourceSkipAclApply()) {
            $this->aclHelper->apply($qb, BasicPermission::EDIT);
        }

        $manager = $this->doctrineHelper->getEntityManagerForClass(Coupon::class);

        $iteration = 0;
        foreach ($qb->getQuery()->iterate(null, Query::HYDRATE_SCALAR) as $result) {
            $sourceParams = reset($result);
            /** @var Coupon $coupon */
            $coupon = $manager->getRepository(Coupon::class)->find($sourceParams['id']);
            if ($coupon) {
                $this->execute($coupon, $args);

                $iteration++;
                if ($iteration % self::FLUSH_BATCH_SIZE === 0) {
                    $manager->flush();
                    $manager->clear();
                }
            }
        }

        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $manager->flush();
            $manager->clear();
        }

        return $this->getResponse($iteration);
    }

    abstract protected function execute(Coupon $coupon, MassActionHandlerArgs $args);

    /**
     * @param int $entitiesCount
     * @return MassActionResponse
     */
    abstract protected function getResponse($entitiesCount);
}
