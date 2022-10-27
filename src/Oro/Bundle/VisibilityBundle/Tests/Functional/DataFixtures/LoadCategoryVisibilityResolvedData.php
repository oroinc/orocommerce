<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Symfony\Component\Yaml\Yaml;

class LoadCategoryVisibilityResolvedData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        $categories = $this->getCategoryVisibilityData();

        foreach ($categories as $categoryReference => $categoryVisibilityData) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $this->createCategoryVisibilities($category, $categoryVisibilityData);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryVisibilityData::class
        ];
    }

    protected function createCategoryVisibilities(Category $category, array $categoryData)
    {
        $this->createCategoryVisibility($category, $categoryData['all']);

        $this->createCustomerGroupCategoryVisibilities($category, $categoryData['groups']);

        $this->createCustomerCategoryVisibilities($category, $categoryData['customers']);
    }

    protected function createCategoryVisibility(Category $category, array $data)
    {
        if (!$data['visibility']) {
            return;
        }

        $categoryVisibility = (new CategoryVisibilityResolved($category))
            ->setVisibility($data['visibility']);

//        $this->setReference($data['reference'], $categoryVisibility);

        $this->em->persist($categoryVisibility);
    }

    protected function createCustomerGroupCategoryVisibilities(Category $category, array $customerGroupVisibilityData)
    {
        foreach ($customerGroupVisibilityData as $customerGroupReference => $data) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($customerGroupReference);
            $customerGroupCategoryVisibility = (new CustomerGroupCategoryVisibilityResolved($category, $customerGroup))
                ->setVisibility($data['visibility']);

//            $this->setReference($data['reference'], $customerGroupCategoryVisibility);

            $this->em->persist($customerGroupCategoryVisibility);
        }
    }

    protected function createCustomerCategoryVisibilities(Category $category, array $customerVisibilityData)
    {
        foreach ($customerVisibilityData as $customerReference => $data) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerReference);
            $customerCategoryVisibility = (new CustomerCategoryVisibilityResolved($category, $customer))
                ->setVisibility($data['visibility']);

//            $this->setReference($data['reference'], $customerCategoryVisibility);

            $this->em->persist($customerCategoryVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getCategoryVisibilityData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'category_visibilities_resolved.yml';

        return Yaml::parse(file_get_contents($filePath));
    }
}
