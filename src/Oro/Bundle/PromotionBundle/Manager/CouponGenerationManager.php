<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\CouponGeneration\Generator\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Manage Coupon Generation Services architecture and functional
 */
class CouponGenerationManager
{
    const BULK_SIZE = 500;

    /**
     * @var CodeGeneratorInterface
     */
    protected $couponGenerator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param CodeGeneratorInterface $couponGenerator
     * @param DoctrineHelper $doctrineHelper
     * @internal param CouponInserterInterface $couponInserter
     */
    public function __construct(CodeGeneratorInterface $couponGenerator, DoctrineHelper $doctrineHelper)
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
        $inserted = 0;

        while ($inserted < $options->getCouponQuantity()) {
            $this->getConnection()->beginTransaction();
            try {
                $requiredAmount = $options->getCouponQuantity() - $inserted;
                $bulkSize = $requiredAmount > self::BULK_SIZE ? self::BULK_SIZE : $requiredAmount;
                $codes = $this->getUniqueCodes($options, $bulkSize);
                if ($codes) {
                    $insertStatement = $this->getInsertStatement(count($codes), $options);
                    foreach ($codes as $key => $code) {
                        $insertStatement->bindValue("code$key", $code);
                        $inserted++;
                    }
                    $insertStatement->execute();
                }
                $this->getConnection()->commit();
            } catch (\Exception $e) {
                $this->getConnection()->rollBack();
                throw $e;
            }
        }

        $this->insertStatements = [];
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        return $this->doctrineHelper->getEntityManager(Coupon::class)->getConnection();
    }

    /**
     * @param $amount
     * @return Statement
     */
    protected function getInsertStatement($amount, CouponGenerationOptions $options)
    {
        $sql = '
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
            ) VALUES
        ';
        $placeholders = [];
        for ($i = 0; $i < $amount; $i++) {
            $placeholders[] = "
                (
                  :organization_id,
                  :business_unit_owner_id,
                  :promotion_id,
                  :code$i,
                  :uses_per_coupon,
                  :uses_per_user,
                  :created_at,
                  :updated_at,
                  :valid_until
                )
            ";
        }
        $statement = $this->getConnection()->prepare($sql . implode(',', $placeholders));

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

        return $statement;
    }

    /**
     * @param CouponGenerationOptions $options
     * @param int $amount
     * @return array
     */
    protected function getUniqueCodes(CouponGenerationOptions $options, $amount)
    {
        $codes = $this->couponGenerator->generateUnique($options, $amount);
        $select = $this->getConnection()
            ->prepare("SELECT code FROM oro_promotion_coupon WHERE code IN ('" . implode("','", $codes) . "')");
        $select->execute();

        while ($existingCode = $select->fetchColumn(0)) {
            unset($codes[$existingCode]);
        }
        return array_values($codes);
    }
}
