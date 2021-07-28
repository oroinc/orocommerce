<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Behat\Context;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class SearchIndexContext extends OroFeatureContext
{
    private ManagerRegistry $managerRegistry;

    private WebsiteManager $websiteManager;

    private QueryFactoryInterface $queryFactory;

    public function __construct(
        ManagerRegistry $managerRegistry,
        WebsiteManager $websiteManager,
        QueryFactoryInterface $queryFactory
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->websiteManager = $websiteManager;
        $this->queryFactory = $queryFactory;
    }

    /**
     * Given should be 30 items in "oro_product" website search index
     *
     * @Given /^should be (?P<count>\d+) items in "(?P<index>[^']+)" website search index$/
     */
    public function shouldBeItemsInSearchIndex(int $count, string $index): void
    {
        $this->websiteManager->setCurrentWebsite($this->getWebsite());

        $result = $this->spin(
            function () use ($count, $index) {
                $query = $this->queryFactory->create(
                    [
                        'search_index' => 'website',
                        'query' => [
                            'select' => [],
                            'from' => [$index],
                        ]
                    ]
                );

                return $count === $query->getResult()
                        ->getRecordsCount();
            },
            5
        );

        self::assertTrue(
            $result,
            sprintf(
                'The count of "%s" items in index is not equal to the expected %s',
                $index,
                $count
            )
        );
    }

    private function getWebsite(): Website
    {
        $repository = $this->managerRegistry
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);

        return $repository->findOneBy([]);
    }
}
