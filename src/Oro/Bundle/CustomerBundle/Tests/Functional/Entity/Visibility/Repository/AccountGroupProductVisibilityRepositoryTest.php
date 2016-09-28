<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CustomerBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /** @var AccountGroupProductVisibilityRepository */
    protected $repository;

    /** @var  RegistryInterface */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroCustomerBundle:Visibility\AccountGroupProductVisibility'
        );

        $this->loadFixtures(
            ['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']
        );
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:Visibility\AccountGroupProductVisibility');
    }

    /**
     * @return array
     */
    public function setToDefaultWithoutCategoryDataProvider()
    {
        return [
            [
                'category' => LoadCategoryData::FOURTH_LEVEL2,
                'deletedCategoryProducts' => ['product.8'],
            ],
        ];
    }
}
