<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Performance\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class LoadQuoteDataForPerformanceTest extends LoadQuoteData
{
    const NUMBER_OF_QUOTE_GROUPS = 2100000 / 7;

    protected $nrOfQuotesToExpire = 870;

    protected static $quoteUpdateFields = [
        'user_owner_id',
        'qid',
        'organization_id',
        'ship_until',
        'po_number',
        'created_at',
        'updated_at',
        'locked',
        'expired',
        'valid_until'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $insertQuoteBaseSql = $this->getUpdateQuotesBaseSql();

        // generate sprintf string for insert values
        foreach (self::$quoteUpdateFields as $field) {
            $params[] = "'%s'";
        }
        $valueSprintf = '(' . implode(', ', $params) . '),';

        for ($i = 1; $i <= static::NUMBER_OF_QUOTE_GROUPS; $i++) {
            $quoteSql = $insertQuoteBaseSql;
            foreach (self::$items as $item) {
                $poNumber = 'CA' . rand(1000, 9999) . 'USD';

                // generate VALUES sql
                $quoteSql .= sprintf($valueSprintf,
                    $user->getId(),
                    $item['qid'],
                    $user->getOrganization()->getId(),
                    (new \DateTime('+10 day'))->format('Y-m-d'),
                    $poNumber,
                    (new \DateTime())->format('Y-m-d'),
                    (new \DateTime())->format('Y-m-d'),
                    0,
                    $this->getExpiredValue(),
                    $this->getValidUntilValue()
                );
            }
            $quoteSql = substr($quoteSql, 0, -1) . ';';
            $manager->getConnection()->exec($quoteSql);
        }
    }

    /**
     * @return string
     */
    protected function getUpdateQuotesBaseSql()
    {
        $sql = 'INSERT INTO `orob2b_sale_quote` (';
        foreach (self::$quoteUpdateFields as $field) {
            $sql .= '`' . $field . '`, ';
        }
        $sql = substr($sql, 0, -2) . ') VALUES ';

        return $sql;
    }

    /**
     * @return int
     */
    protected function getExpiredValue()
    {
        if ($this->nrOfQuotesToExpire > 0) {
            $this->nrOfQuotesToExpire--;

            return 0;
        }

        return 1;
    }

    /**
     * @return string
     */
    protected function getValidUntilValue()
    {
        return $this->nrOfQuotesToExpire > 0
            ? (new \DateTime('-1days'))->format('Y-m-d H:i:s')
            : (new \DateTime('+1days'))->format('Y-m-d H:i:s');
    }
}
