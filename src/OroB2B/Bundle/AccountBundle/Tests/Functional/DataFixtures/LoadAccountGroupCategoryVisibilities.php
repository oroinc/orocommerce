<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class LoadAccountGroupCategoryVisibilities extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => 'account_group_category_visibility.1',
            'category' => 'Test First Level',
            'group' => 'account_group.group1',
        ],
        [
            'name' => 'account_group_category_visibility.2',
            'category' => 'Test Second Level',
            'group' => 'account_group.group1',
        ],
        [
            'name' => 'account_group_category_visibility.3',
            'category' => 'Test Second Level',
            'group' => 'account_group.group2',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAccountGroupCategoryVisibility($manager);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createAccountGroupCategoryVisibility(ObjectManager $manager)
    {
        foreach (self::$data as $visibilityData) {
            /** @var Category $category */
            $category = $this->getReference($visibilityData['category']);
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($visibilityData['group']);

            $visibility = (new AccountGroupCategoryVisibility())
                ->setCategory($category)
                ->setAccountGroup($accountGroup);
            $manager->persist($visibility);
            $this->addReference($visibilityData['name'], $visibility);
        }
    }
}
