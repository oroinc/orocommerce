<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\AccountGroupCategoryResolvedCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupCategoryResolvedCacheBuilderTest extends AbstractProductResolvedCacheBuilderTest
{
    /** @var Category */
    protected $category;

    /** @var AccountGroup */
    protected $accountGroup;

    /** @var AccountGroupCategoryResolvedCacheBuilder */
    protected $builder;

    protected function setUp()
    {
        parent::setUp();
        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->accountGroup = $this->getReference('account_group.group3');

        $container = $this->client->getContainer();

        $this->builder = new AccountGroupCategoryResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $this->builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.account_group_category_visibility_resolved.class')
        );
        
        $subtreeBuilder = new VisibilityChangeGroupSubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('orob2b_account.visibility.resolver.category_visibility_resolver'),
            $container->get('oro_config.manager')
        );

        $this->builder->setVisibilityChangeAccountSubtreeCacheBuilder($subtreeBuilder);
    }

    public function testChangeAccountGroupCategoryVisibilityToHidden()
    {
        $visibility = new AccountGroupCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setAccountGroup($this->accountGroup);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');
        $em->persist($visibility);
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToHidden
     */
    public function testChangeAccountGroupCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToHidden
     */
    public function testChangeAccountGroupCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountGroupCategoryVisibility::PARENT_CATEGORY);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals(
            $visibility->getVisibility(),
            $visibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved['visibility']
        );
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToParentCategory
     */
    public function testChangeAccountGroupCategoryVisibilityToAll()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountGroupCategoryVisibility::CATEGORY);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertNull($visibilityResolved);
    }

    /**
     * @return array
     */
    protected function getVisibilityResolved()
    {
        /** @var EntityManager $em */
        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
        $qb = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->createQueryBuilder('accountCategoryVisibilityResolved');
        $entity = $qb->select('accountCategoryVisibilityResolved', 'accountCategoryVisibility')
            ->leftJoin('accountCategoryVisibilityResolved.sourceCategoryVisibility', 'accountCategoryVisibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('accountCategoryVisibilityResolved.accountGroup', ':accountGroup')
                )
            )
            ->setParameters([
                'category' => $this->category,
                'accountGroup' => $this->accountGroup,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return $entity;
    }

    /**
     * @return null|AccountGroupCategoryVisibility
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->findOneBy(['category' => $this->category, 'accountGroup' => $this->accountGroup]);
    }

    /**
     * @param array $categoryVisibilityResolved
     * @param VisibilityInterface $categoryVisibility
     * @param integer $expectedVisibility
     */
    protected function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($categoryVisibilityResolved);
        $this->assertEquals($this->category->getId(), $categoryVisibilityResolved['category_id']);
        $this->assertEquals($this->accountGroup->getId(), $categoryVisibilityResolved['account_group_id']);
        $this->assertEquals(
            AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
            $categoryVisibilityResolved['source']
        );
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }

    /**
     * @dataProvider buildCacheDataProvider
     * @param array $expectedVisibilities
     */
    public function testBuildCache(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->replaceReferencesWithIds($expectedVisibilities);
        usort($expectedVisibilities, [$this, 'sortByCategoryAndAccountGroup']);

        $this->builder->buildCache();

        $actualVisibilities = $this->getResolvedVisibilities();
        usort($actualVisibilities, [$this, 'sortByCategoryAndAccountGroup']);

        $this->assertEquals($expectedVisibilities, $actualVisibilities);
    }

    /**
     * @return array
     */
    public function buildCacheDataProvider()
    {
        return [
            [
                'expectedVisibilities' => [
                    [
                        'category' => 'category_1',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group2'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'accountGroup' => 'account_group.group2'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3'
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'accountGroup' => 'account_group.group3'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByCategoryAndAccountGroup(array $a, array $b)
    {
        if ($a['category'] == $b['category']) {
            return $a['accountGroup'] > $b['accountGroup'] ? 1 : -1;
        }

        return $a['category'] > $b['category'] ? 1 : -1;
    }

    /**
     * @param array $visibilities
     * @return array
     */
    protected function replaceReferencesWithIds(array $visibilities)
    {
        $rootCategory = $this->getRootCategory();
        foreach ($visibilities as $key => $row) {
            $category = $row['category'];
            /** @var Category $category */
            if ($category === self::ROOT) {
                $category = $rootCategory;
            } else {
                $category = $this->getReference($category);
            }

            $visibilities[$key]['category'] = $category->getId();

            /** @var AccountGroup $category */
            $accountGroup = $this->getReference($row['accountGroup']);
            $visibilities[$key]['accountGroup'] = $accountGroup->getId();
        }
        return $visibilities;
    }

    /**
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'IDENTITY(entity.accountGroup) as accountGroup',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
