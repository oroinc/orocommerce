<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
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

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroVisibilityBundle:Visibility\AccountGroupProductVisibility'
        );

        $this->loadFixtures(
            ['Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']
        );
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility');
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
