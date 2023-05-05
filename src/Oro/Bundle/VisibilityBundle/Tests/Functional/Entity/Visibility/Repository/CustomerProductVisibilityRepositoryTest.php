<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

class CustomerProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductVisibilityData::class]);
        $this->repository = $this->getContainer()->get('doctrine')->getRepository(CustomerProductVisibility::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setToDefaultWithoutCategoryDataProvider(): array
    {
        return [
            [
                'category' => LoadCategoryData::FOURTH_LEVEL2,
                'deletedCategoryProducts' => ['product-8'],
            ],
        ];
    }
}
