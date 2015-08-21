<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class LoadAccountCategoryVisibilities extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => 'account_category_visibility.1',
            'category' => 'Test First Level',
            'account' => 'account.level_1',
        ],
        [
            'name' => 'account_category_visibility.2',
            'category' => 'Test Second Level',
            'account' => 'account.level_1',
        ],
        [
            'name' => 'account_category_visibility.3',
            'category' => 'Test Second Level',
            'account' => 'account.level_1.4',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAccountCategoryVisibility($manager);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createAccountCategoryVisibility(ObjectManager $manager)
    {
        foreach (self::$data as $visibilityData) {
            /** @var Category $category */
            $category = $this->getReference($visibilityData['category']);
            /** @var Account $account */
            $account = $this->getReference($visibilityData['account']);

            $visibility = (new AccountCategoryVisibility())
                ->setCategory($category)
                ->setAccount($account);
            $manager->persist($visibility);
            $this->addReference($visibilityData['name'], $visibility);
        }
    }
}
