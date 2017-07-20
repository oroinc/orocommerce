<?php

namespace Oro\Bundle\CouponBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\Query;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Exception\UnexpectedTypeException;
use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Form\Type\BaseCouponType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class CouponEditMassActionHandler implements MassActionHandlerInterface
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

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param DoctrineHelper $helper
     * @param AclHelper $aclHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DoctrineHelper $helper, AclHelper $aclHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $helper;
        $this->translator = $translator;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $datasource = $args->getDatagrid()->getDatasource();

        if (!$datasource instanceof OrmDatasource) {
            throw new UnexpectedTypeException($datasource, OrmDatasource::class);
        }

        $qb = clone $datasource->getQueryBuilder();
        if (!$args->getDatagrid()->getConfig()->isDatasourceSkipAclApply()) {
            $this->aclHelper->apply($qb, 'EDIT');
        }

        $manager = $this->doctrineHelper->getEntityManagerForClass(Coupon::class);
        $formData = $this->getFormData($args);
        $owner = $this->doctrineHelper->getEntityRepositoryForClass(BusinessUnit::class)->find($formData['owner']);

        $iteration = 0;
        foreach ($qb->getQuery()->iterate(null, Query::HYDRATE_SCALAR) as $result) {
            $sourceParams = reset($result);
            /** @var Coupon $coupon */
            $coupon = $manager->getRepository(Coupon::class)->find($sourceParams['id']);
            if ($coupon) {
                $coupon->setUsesPerCoupon($this->normalize($formData['usesPerCoupon']));
                $coupon->setUsesPerUser($this->normalize($formData['usesPerUser']));
                $coupon->setOwner($owner);

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

        return $this->getEditResponse($iteration);
    }

    /**
     * @param MassActionHandlerArgs $args
     * @return array
     */
    protected function getFormData(MassActionHandlerArgs $args)
    {
        $requestData = $args->getData();

        if (!array_key_exists(BaseCouponType::NAME, $requestData) || !is_array($requestData[BaseCouponType::NAME])) {
            throw new LogicException('Required array with form data not found');
        }

        return $requestData[BaseCouponType::NAME];
    }

    /**
     * @param int $entitiesCount
     * @return MassActionResponse
     */
    protected function getEditResponse($entitiesCount)
    {
        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->transChoice(
                'oro.grid.mass_action.edit.success_message',
                $entitiesCount,
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }

    /**
     * @param string $value
     * @return int|null
     */
    protected function normalize($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value !== false ? $value : null;
    }
}
