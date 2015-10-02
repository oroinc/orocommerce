<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\CategoryVisibilityRepository;

class CategoryVisibilityRepositoryTest extends WebTestCase
{
    /**
     * @var CategoryVisibilityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities',
            ]
        );
    }

    /**
     * dataProvider getVisibilityToAllDataProvider
     */
    public function testGetVisibilityToAll()
    {
        $account = $this->getReference('account.level_1');
        /** @var array $actual */
        $actual = $this->repository->getVisibilityToAll($account->getId());
//        $expected = [];
//        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getVisibilityToAllDataProvider()
    {
        return [];
    }
}
