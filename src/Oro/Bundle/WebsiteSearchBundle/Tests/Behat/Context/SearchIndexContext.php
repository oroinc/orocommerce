<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class SearchIndexContext extends OroFeatureContext
{
    use KernelDictionary;

    /**
     * Given should be 30 items in "oro_product" website search index
     *
     * @Given /^should be (?P<count>\d+) items in "(?P<index>[^']+)" website search index$/
     */
    public function shouldBeItemsInSearchIndex(int $count, string $index): void
    {
        $this->getContainer()
            ->get('oro_website.manager')
            ->setCurrentWebsite($this->getWebsite());

        $queryFactory = $this->getContainer()
            ->get('oro_website_search.query_factory');

        $result = $this->spin(
            static function () use ($count, $queryFactory, $index) {
                $query = $queryFactory->create(
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
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);

        return $repository->findOneBy([]);
    }
}
