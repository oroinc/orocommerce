<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class CategoryListenerTest extends WebTestCase
{

    /**
     * @var ProductVisibilityRepository
     */
    protected $repository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;
    /**
     * @var ProductVisibilityResolvedRepository
     */
    protected $visibilityResolvedRepository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility');

        $this->visibilityResolvedRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $this->categoryRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    /**
     * @dataProvider changeProductCategoryDataProvider
     * @param $productName
     * @param $categoryName
     * @param $expected
     * @param $expectedResolved
     */
    public function testChangeProductCategory($productName, $categoryName, $expected, $expectedResolved)
    {
        /**
         * @var $product Product
         */
        $product = $this->getReference($productName);

        /**
         * @var $category Category
         */
        $category = $this->getReference($categoryName);
        $lastCategory = $this->categoryRepository->findOneByProduct($product);
        $category = $this->categoryRepository->find($category->getId());

        if ($lastCategory) {
            $lastCategory->removeProduct($product);
        }
        $category->addProduct($product);
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->flush([$lastCategory, $category]);
        $actual = $this->getVisibilityByProduct($product);
        $actualResolved = $this->getVisibilityResolvedByProduct($product);

        $this->assertSameSize($expected, $actual);
        foreach ($actual as $value) {
            $this->assertContains($value, $expected);
        }
        $this->assertSameSize($expectedResolved, $actualResolved);
        foreach ($actualResolved as $key => $value) {
            $this->assertSame($value['source'], $expectedResolved[$key]['source']);
            $this->assertSame((int)$value['category'], $category->getId());
        }
    }

    /**
     * @return array
     */
    public function changeProductCategoryDataProvider()
    {
        return [
            'change_category' => [
                'productName' => LoadProductData::PRODUCT_1,
                'categoryName' => LoadCategoryData::SECOND_LEVEL1,
                'expected' => [
                    [
                        'visibility' => ProductVisibility::CONFIG,
                    ]

                ],
                'expectedResolved' => [
                    [
                        'source' => ProductVisibilityResolved::SOURCE_CATEGORY,
                    ],
                    [
                        'source' => ProductVisibilityResolved::SOURCE_CATEGORY,
                    ]
                ],
            ],
        ];
    }


    /**
     * @return array
     */
    protected function getProductsByVisibilities()
    {
        $website = $this->getDefaultWebsite();
        return array_map(
            function (ProductVisibility $visibility) {
                return [
                    'product' => $visibility->getProduct()->getSku(),
                    'website' => $visibility->getWebsite()->getName(),
                    'visibility' => $visibility->getVisibility()
                ];
            },
            $this->repository->findBy(['website' => $website])
        );
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();
    }

    /**
     * @param $product
     * @return array
     */
    protected function getVisibilityByProduct($product)
    {
        $qb = $this->repository->createQueryBuilder('v')
            ->select('v.visibility')
            ->where('v.product = :product')
            ->setParameter('product', $product);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $product
     * @return array
     */
    protected function getVisibilityResolvedByProduct($product)
    {
        $qb = $this->visibilityResolvedRepository->createQueryBuilder('v')
            ->select(['v.source', 'IDENTITY(v.category) as category'])
            ->where('v.product = :product')
            ->setParameter('product', $product);

        return $qb->getQuery()->getArrayResult();
    }
}
