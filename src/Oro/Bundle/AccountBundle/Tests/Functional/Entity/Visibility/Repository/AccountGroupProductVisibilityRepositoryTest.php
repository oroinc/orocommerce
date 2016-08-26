<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroAccountBundle:Visibility\AccountGroupProductVisibility'
        );

        $this->loadFixtures(
            ['Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']
        );
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroAccountBundle:Visibility\AccountGroupProductVisibility');

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
