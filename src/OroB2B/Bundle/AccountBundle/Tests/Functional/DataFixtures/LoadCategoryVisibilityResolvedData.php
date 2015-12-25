<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class LoadCategoryVisibilityResolvedData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        //@TODO: Remove this fixture in BB-1650 implementation
        $this->em = $manager;
        
        $categories = $this->getCategoryVisibilityResolvedData();

        foreach ($categories as $categoryReference => $categoryVisibilityData) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $this->createCategoryVisibilitiesResolved($category, $categoryVisibilityData);
        }

        $manager->flush();
    }

    /**
     * @param Category $category
     * @param array $categoryData
     */
    protected function createCategoryVisibilitiesResolved(Category $category, array $categoryData)
    {
        $this->createCategoryVisibilityResolved($category, $categoryData['all']);

        $this->createAccountGroupCategoryVisibilityResolved($category, $categoryData['groups']);

        $this->createAccountCategoryVisibilityResolved($category, $categoryData['accounts']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
        ];
    }
    
    /**
     * @param Category $category
     * @param array $data
     */
    protected function createCategoryVisibilityResolved(Category $category, array $data)
    {
        $categoryVisibility = (new CategoryVisibilityResolved($category))
            ->setVisibility($this->convertVisibility($data['visibility']));

        $this->em->persist($categoryVisibility);
    }

    /**
     * @param Category $category
     * @param array $accountGroupVisibilityResolvedData
     */
    protected function createAccountGroupCategoryVisibilityResolved(
        Category $category,
        array $accountGroupVisibilityResolvedData
    ) {
        foreach ($accountGroupVisibilityResolvedData as $accountGroupReference => $data) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($accountGroupReference);

            $accountGroupCategoryVisibility = (new AccountGroupCategoryVisibilityResolved($category, $accountGroup))
                ->setVisibility($this->convertVisibility($data['visibility']));

            $this->em->persist($accountGroupCategoryVisibility);
        }
    }

    /**
     * @param Category $category
     * @param array $accountVisibilityResolvedData
     */
    protected function createAccountCategoryVisibilityResolved(Category $category, array $accountVisibilityResolvedData)
    {
        foreach ($accountVisibilityResolvedData as $accountReference => $data) {
            /** @var Account $account */
            $account = $this->getReference($accountReference);
            $accountCategoryVisibility = (new AccountCategoryVisibilityResolved($category, $account))
                ->setVisibility($this->convertVisibility($data['visibility']));

            $this->em->persist($accountCategoryVisibility);
        }
    }

    /**
     * @param $visibility
     * @return int
     */
    protected function convertVisibility($visibility)
    {
        return $visibility
            ? BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            : BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @return array
     */
    protected function getCategoryVisibilityResolvedData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'category_visibilities_resolved.yml';

        return Yaml::parse($filePath);
    }
}
