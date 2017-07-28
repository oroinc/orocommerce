<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\CouponGeneration\Generator\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Manage Coupon Generation Services architecture and functional
 */
class CouponGenerationManager
{
    /**
     * @var CouponGeneratorInterface
     */
    protected $couponGenerator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Statement|null
     */
    protected $insertStatement;

    /**
     * @param CouponGeneratorInterface $couponGenerator
     * @param DoctrineHelper $doctrineHelper
     * @internal param CouponInserterInterface $couponInserter
     */
    public function __construct(CouponGeneratorInterface $couponGenerator, DoctrineHelper $doctrineHelper)
    {
        $this->couponGenerator = $couponGenerator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Generate set of coupons based on user defined generation parameters
     *
     * @param CouponGenerationOptions $options
     * @throws \Exception
     */
    public function generateCoupons(CouponGenerationOptions $options)
    {
        $this->getConnection()->transactional(function (Connection $conn) use ($options) {

            $checkStatement = $conn->prepare('SELECT 1 FROM oro_promotion_coupon WHERE code = :code');

            $statement = $this->getInsertStatement();
            $statement->bindValue(
                'organization_id',
                $options->getOwner() ? $options->getOwner()->getOrganization()->getId() : null
            );
            $statement->bindValue(
                'business_unit_owner_id',
                $options->getOwner() ? $options->getOwner()->getId() : null
            );
            $statement->bindValue(
                'promotion_id',
                $options->getPromotion() ? $options->getPromotion()->getId() : null
            );
            $statement->bindValue('uses_per_coupon', $options->getUsesPerCoupon());
            $statement->bindValue('uses_per_user', $options->getUsesPerUser());
            $statement->bindValue('valid_until', $options->getExpirationDate()->format('Y-m-d H:i:s'));
            $statement->bindValue('created_at', date('Y-m-d H:i:s'));
            $statement->bindValue('updated_at', date('Y-m-d H:i:s'));

            for ($i = 0; $i < $options->getCouponQuantity(); $i++) {
                $code = $this->couponGenerator->generate($options);
                $checkStatement->execute(['code' => $code]);
                if ($checkStatement->fetchColumn(0)) {
                    $i--; //retry
                } else {
                    $statement->bindValue('code', $code);
                    $statement->execute();
                }
            }
        });
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        return $this->doctrineHelper->getEntityManager(Coupon::class)->getConnection();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getInsertStatement()
    {
        if (!$this->insertStatement) {
            $this->insertStatement = $this->getConnection()->prepare('
                INSERT INTO oro_promotion_coupon (
                  organization_id,
                  business_unit_owner_id,
                  promotion_id,
                  code,
                  uses_per_coupon,
                  uses_per_user,
                  created_at,
                  updated_at,
                  valid_until
                )
                VALUES (
                  :organization_id,
                  :business_unit_owner_id,
                  :promotion_id,
                  :code,
                  :uses_per_coupon,
                  :uses_per_user,
                  :created_at,
                  :updated_at,
                  :valid_until
                )
            ');
        }
        return $this->insertStatement;
    }
}
