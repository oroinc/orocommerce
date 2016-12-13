<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;

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
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroVisibilityBundle:Visibility\AccountProductVisibility'
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility');

        $this->loadFixtures(
            [
                'Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
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
