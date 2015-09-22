<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

class LoadAccountCategoryVisibilities extends AbstractFixture implements DependentFixtureInterface
{
    const VISIBILITY1 = 'account_category_visibility.1';
    const VISIBILITY2 = 'account_category_visibility.2';
    const VISIBILITY3 = 'account_category_visibility.3';
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => self::VISIBILITY1,
            'category' => LoadCategoryData::FIRST_LEVEL,
            'account' => 'account.level_1',
        ],
        [
            'name' => self::VISIBILITY2,
            'category' => LoadCategoryData::SECOND_LEVEL1,
            'account' => 'account.level_1',
        ],
        [
            'name' => self::VISIBILITY3,
            'category' => LoadCategoryData::SECOND_LEVEL1,
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
