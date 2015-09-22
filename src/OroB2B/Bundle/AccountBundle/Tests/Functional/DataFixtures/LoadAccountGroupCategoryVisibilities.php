<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

class LoadAccountGroupCategoryVisibilities extends AbstractFixture implements DependentFixtureInterface
{
    const VISIBILITY1 = 'account_group_category_visibility.1';
    const VISIBILITY2 = 'account_group_category_visibility.2';
    const VISIBILITY3 = 'account_group_category_visibility.3';

    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => self::VISIBILITY1,
            'category' => LoadCategoryData::FIRST_LEVEL,
            'group' => 'account_group.group1',
        ],
        [
            'name' => self::VISIBILITY2,
            'category' => LoadCategoryData::SECOND_LEVEL1,
            'group' => 'account_group.group1',
        ],
        [
            'name' => self::VISIBILITY3,
            'category' => LoadCategoryData::SECOND_LEVEL1,
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
