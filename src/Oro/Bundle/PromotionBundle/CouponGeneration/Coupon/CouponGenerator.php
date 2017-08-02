<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Coupon;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Coupon codes mass generator
 */
class CouponGenerator implements CouponGeneratorInterface
{
    const BULK_SIZE = 1000;

    const LENGTH_SWITCH_THRESHOLD = 0.3;

    const LENGTH_SWITCH_MAX_FAILS = 10;

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
     */
    public function __construct(CodeGeneratorInterface $couponGenerator, DoctrineHelper $doctrineHelper)
    {
        $this->couponGenerator = $couponGenerator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAndSave(CouponGenerationOptions $options)
    {
        $options = clone $options;
        $statistic = [];

        $this->getConnection()->transactional(function (Connection $conn) use (&$statistic, $options) {
            $fails = 0;
            $inserted = 0;
            while ($inserted < $options->getCouponQuantity()) {
                $requiredAmount = $options->getCouponQuantity() - $inserted;
                $bulkSize = $requiredAmount > self::BULK_SIZE ? self::BULK_SIZE : $requiredAmount;
                $codes = $this->getUniqueCodes($options, $bulkSize);

                if ($codes) {
                    $statement = $this->getInsertStatement($options, count($codes));
                    foreach ($codes as $key => $code) {
                        $statement->bindValue("code$key", $code);
                    }
                    $statement->execute();
                    $inserted += count($codes);
                }

                array_key_exists($options->getCodeLength(), $statistic) ?
                    $statistic[$options->getCodeLength()] += count($codes) :
                    $statistic[$options->getCodeLength()] = count($codes);

                if (count($codes) / self::BULK_SIZE < self::LENGTH_SWITCH_THRESHOLD) {
                    $fails++;
                }
                if ($fails > self::LENGTH_SWITCH_MAX_FAILS) {
                    $options->setCodeLength($options->getCodeLength() + 1);
                    $fails = 0;
                }
            }
        });

        return $statistic;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->doctrineHelper->getEntityManager(Coupon::class)->getConnection();
    }

    /**
     * @param CouponGenerationOptions $options
     * @param int $amount
     * @return Statement
     */
    protected function getInsertStatement(CouponGenerationOptions $options, int $amount): Statement
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
        $connection = $this->getConnection();
        $statement = $connection->prepare($sql . implode(',', $placeholders));

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
        $statement->bindValue(
            'valid_until',
            $connection->convertToDatabaseValue($options->getExpirationDate(), Type::DATETIME)
        );
        $statement->bindValue(
            'created_at',
            $connection->convertToDatabaseValue(new \DateTime(), Type::DATETIME)
        );
        $statement->bindValue(
            'updated_at',
            $connection->convertToDatabaseValue(new \DateTime(), Type::DATETIME)
        );

        return $statement;
    }

    /**
     * @param CouponGenerationOptions $options
     * @param int $amount
     * @return array
     */
    protected function getUniqueCodes(CouponGenerationOptions $options, int $amount): array
    {
        $codes = $this->couponGenerator->generateUnique($options, $amount);
        if ($codes) {
            $statement = $this->getConnection()
                ->prepare("SELECT code FROM oro_promotion_coupon WHERE code IN ('" . implode("','", $codes) . "')");
            $statement->execute();

            while ($existingCode = $statement->fetchColumn(0)) {
                unset($codes[$existingCode]);
            }
        }
        return array_values($codes);
    }
}
