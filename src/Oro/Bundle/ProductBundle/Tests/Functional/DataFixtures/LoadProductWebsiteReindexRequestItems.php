<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadProductWebsiteReindexRequestItems extends AbstractFixture
{
    public const JOB_ID_W_PRODUCT_IN_DIFFERENT_WEBSITES = 1;
    public const JOB_ID_W_PRODUCT_IN_SAME_WEBSITES = 2;

    private array $requestItemData = [
        self::JOB_ID_W_PRODUCT_IN_DIFFERENT_WEBSITES => [
            1 => [5, 4, 3, 2, 1],
            2 => [5, 4, 3, 2, 1],
            3 => [1, 3, 2, 4, 5],
            4 => [1, 3, 2, 4, 5],
            5 => [1, 3, 2],
            6 => [1, 2, 3],
            7 => [3, 2, 1],
            8 => [3],
            9 => [3],
            10 => [1],
        ],
        self::JOB_ID_W_PRODUCT_IN_SAME_WEBSITES => [
            1 => [5, 4, 3, 2, 1],
            2 => [5, 4, 3, 2, 1],
            3 => [1, 3, 2, 4, 5],
            4 => [1, 3, 2, 4, 5],
        ]
    ];

    public function load(ObjectManager $manager)
    {
        /** @var Connection $connection */
        $connection = $manager->getConnection();

        $params = [];
        foreach ($this->requestItemData as $relatedJobId => $productIdWithWebsiteIds) {
            foreach ($productIdWithWebsiteIds as $productId => $websiteIds) {
                foreach ($websiteIds as $websiteId) {
                    $params[] = $relatedJobId;
                    $params[] = $websiteId;
                    $params[] = $productId;
                }
            }
        }

        $rowTemplate = '(?,?,?)'; // related_job_id, website_id, product_id
        $rows = array_fill(0, count($params) / 3, $rowTemplate);
        $sqlStatement = sprintf(
            'INSERT INTO oro_prod_webs_reindex_req_item (related_job_id, website_id, product_id) VALUES %s',
            implode(',', $rows)
        );
        $connection->executeStatement(
            $sqlStatement,
            $params
        );
    }
}
