<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;

/**
 * @dbIsolation
 */
class AccountProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /** @var AccountProductVisibilityRepository */
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
            'OroAccountBundle:Visibility\AccountProductVisibility'
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroAccountBundle:Visibility\AccountProductVisibility');

        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    /**
     * {@inheritdoc}
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
