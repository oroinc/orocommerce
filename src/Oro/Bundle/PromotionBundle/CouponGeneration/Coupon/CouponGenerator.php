<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Coupon;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Psr\Log\LoggerInterface;

/**
 * This class generates and inserts coupon codes to database
 */
class CouponGenerator implements CouponGeneratorInterface
{
    // Number of codes to generate and insert
    const BULK_SIZE = 1000;

    // Threshold which shows when system should increase coupon code length
    // If number_of_new_unique_codes / number_of_generated_codes < LENGTH_SWITCH_THRESHOLD code length will be increased
    // after LENGTH_SWITCH_MAX_FAILS attempts
    const LENGTH_SWITCH_THRESHOLD = 0.25;

    // Number of attempts to insert required number of codes before increase coupon code length
    const LENGTH_SWITCH_MAX_FAILS = 10;

    /**
     * @var Statement[]
     */
    protected $insertStatements = [];

    /**
     * @var CodeGeneratorInterface
     */
    protected $couponGenerator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        CodeGeneratorInterface $couponGenerator,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger
    ) {
        $this->couponGenerator = $couponGenerator;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
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
                $bulkSize = min(self::BULK_SIZE, $options->getCouponQuantity() - $inserted);
                $generatedCodes = $this->couponGenerator->generateUnique($options, $bulkSize);
                $generatedCodesCount = count($generatedCodes);

                $filteredCodes = $this->filter($generatedCodes);
                $filteredCodesCount = count($filteredCodes);

                if ($filteredCodes) {
                    $this->insertCodes($options, $filteredCodes);
                    $inserted += $filteredCodesCount;
                }

                $this->handleStatistic($statistic, $options, $filteredCodesCount);

                if ($filteredCodesCount / $generatedCodesCount < self::LENGTH_SWITCH_THRESHOLD) {
                    $fails++;
                }

                if ($fails > self::LENGTH_SWITCH_MAX_FAILS || $generatedCodesCount < $bulkSize) {
                    $options->setCodeLength($options->getCodeLength() + 1);
                    $fails = 0;
                }
            }
        });

        $this->logStatistic($statistic);
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->doctrineHelper->getEntityManager(Coupon::class)->getConnection();
    }

    protected function getInsertStatement(CouponGenerationOptions $options, int $count): Statement
    {
        $connection = $this->getConnection();

        if (!isset($this->insertStatements[$count])) {
            $this->insertStatements[$count] = $this->createInsertStatement($count);
        }

        $statement = $this->insertStatements[$count];
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
        $statement->bindValue('enabled', $options->isEnabled(), Types::BOOLEAN);
        $statement->bindValue('uses_per_coupon', $options->getUsesPerCoupon());
        $statement->bindValue('uses_per_person', $options->getUsesPerPerson());
        $statement->bindValue(
            'valid_from',
            $connection->convertToDatabaseValue($options->getValidFrom(), Types::DATETIME_MUTABLE)
        );
        $statement->bindValue(
            'valid_until',
            $connection->convertToDatabaseValue($options->getValidUntil(), Types::DATETIME_MUTABLE)
        );
        $statement->bindValue(
            'created_at',
            $connection->convertToDatabaseValue(new \DateTime(), Types::DATETIME_MUTABLE)
        );
        $statement->bindValue(
            'updated_at',
            $connection->convertToDatabaseValue(new \DateTime(), Types::DATETIME_MUTABLE)
        );

        return $statement;
    }

    /**
     * @param array|string[] $codes
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function filter(array $codes): array
    {
        $statement = $this->getConnection()->executeQuery(
            'SELECT code FROM oro_promotion_coupon WHERE code IN (?)',
            [$codes],
            [Connection::PARAM_STR_ARRAY]
        );

        $assocCodes = array_flip($codes);

        while ($existingCode = $statement->fetchColumn()) {
            unset($assocCodes[$existingCode]);
        }

        return array_keys($assocCodes);
    }

    /**
     * Insert codes to DB
     *
     * @param CouponGenerationOptions $options
     * @param array $filteredCodes
     * @return bool
     */
    protected function insertCodes(CouponGenerationOptions $options, array $filteredCodes)
    {
        $filteredCodesCount = count($filteredCodes);
        $statement = $this->getInsertStatement($options, $filteredCodesCount);

        foreach ($filteredCodes as $index => $code) {
            $statement->bindValue('code' . $index, $code);
        }

        return $statement->execute();
    }

    /**
     * Create new Doctrine insert Statement
     *
     * @param int $count number of insert placeholders
     * @return Statement
     */
    protected function createInsertStatement(int $count)
    {
        $sql = '
            INSERT INTO oro_promotion_coupon (
              organization_id,
              business_unit_owner_id,
              promotion_id,
              code,
              code_uppercase,
              enabled,
              uses_per_coupon,
              uses_per_person,
              created_at,
              updated_at,
              valid_from,
              valid_until
            ) VALUES
        ';
        $placeholders = [];
        for ($i = 0; $i < $count; $i++) {
            $placeholders[] = "
                (
                  :organization_id,
                  :business_unit_owner_id,
                  :promotion_id,
                  :code$i,
                  UPPER(:code$i),
                  :enabled,
                  :uses_per_coupon,
                  :uses_per_person,
                  :created_at,
                  :updated_at,
                  :valid_from,
                  :valid_until
                )
            ";
        }

        return $this->getConnection()->prepare($sql . implode(',', $placeholders));
    }

    /**
     * @param array $statistic
     * @param CouponGenerationOptions $options
     * @param int $numberOfInsertedCodes
     * @return array array of statistic.
     */
    protected function handleStatistic(array &$statistic, CouponGenerationOptions $options, int $numberOfInsertedCodes)
    {
        if (!array_key_exists($options->getCodeLength(), $statistic)) {
            $statistic[$options->getCodeLength()] = 0;
        }

        $statistic[$options->getCodeLength()] += $numberOfInsertedCodes;

        return $statistic;
    }

    protected function logStatistic(array $statistic)
    {
        $numberOfGeneratedCodes = array_sum($statistic);
        $this->logger->info('{generatedCodes} coupon codes were generated.', [
            'generatedCodes' => $numberOfGeneratedCodes,
            'statistic' => $statistic,
        ]);
    }
}
