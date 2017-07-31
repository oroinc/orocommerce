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
     * @var Statement|null
     */
    protected $insertStatement;

    /**
     * @var Statement|null
     */
    protected $selectStatement;

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

        $inserted = 0;

        while ($inserted < $options->getCouponQuantity()) {
            $this->getConnection()->transactional(function ($conn) use ($statement, $options, &$inserted) {
                foreach ($this->getUniqueCodes($options, self::BULK_SIZE) as $key => $code) {
                    $statement->bindValue("code$key", $code);
                    $inserted++;
                }
                $statement->execute();
            });
        }
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
            $codeValues = [];
            for ($i=0; $i < self::BULK_SIZE; $i++) {
                $codeValues[] =  "
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
            $this->insertStatement = $this->getConnection()->prepare($sql . implode(',', $codeValues));
        }
        return $this->insertStatement;
    }

    protected function getUniqueCodes(CouponGenerationOptions $options, $amount)
    {
        $codes = [];
        do {
            do {
                $code = $this->couponGenerator->generate($options);
            } while (array_key_exists($code, $codes));
            $codes[$code] = $code;
        } while (count($codes) < $amount);

        $newCodes = $codes;
        do {
            $select = $this->getConnection()
                ->prepare("SELECT code FROM oro_promotion_coupon WHERE code IN ('" . implode("','", $newCodes) . "')");
            $select->execute();

            $newCodes = [];
            while ($duplicateCode = $select->fetchColumn(0)) {
                do {
                    $newCode = $this->couponGenerator->generate($options);
                } while (array_key_exists($newCode, $codes));
                unset($codes[$duplicateCode]);
                $codes[$newCode] = $newCode;
                $newCodes[] = $newCode;
            }
        } while ($newCodes);

        return array_values($codes);
    }
}
