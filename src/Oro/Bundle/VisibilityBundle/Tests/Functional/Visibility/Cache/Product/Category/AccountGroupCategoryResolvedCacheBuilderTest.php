<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\AccountGroupCategoryResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;

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

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var Scope
     */
    protected $scope;

    protected function setUp()
    {
        parent::setUp();
        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->accountGroup = $this->getReference('account_group.group3');

        $container = $this->client->getContainer();
        $this->scopeManager = $container->get('oro_scope.scope_manager');
        $this->scope = $this->scopeManager->findOrCreate(
            AccountGroupCategoryVisibility::VISIBILITY_TYPE,
            ['accountGroup' => $this->accountGroup]
        );
        $this->builder = new AccountGroupCategoryResolvedCacheBuilder(
            $container->get('doctrine'),
            $this->scopeManager
        );
        $this->builder->setCacheClass(
            $container->getParameter('oro_visibility.entity.account_group_category_visibility_resolved.class')
        );
        $this->builder->setRepositoryHolder(
            $container->get('oro_visibility.category_repository_holder')
        );
        $this->builder->setAccountGroupCategoryVisibilityHolder(
            $container->get('oro_visibility.account_group_category_repository_holder')
        );
        $subtreeBuilder = new VisibilityChangeGroupSubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_visibility.visibility.resolver.category_visibility_resolver'),
            $container->get('oro_config.manager'),
            $container->get('oro_scope.scope_manager')
        );

        $this->builder->setVisibilityChangeAccountSubtreeCacheBuilder($subtreeBuilder);
    }

    public function testChangeAccountGroupCategoryVisibilityToHidden()
    {
        $visibility = new AccountGroupCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility');
        $em->persist($visibility);
        $em->flush();
        $this->builder->buildCache();
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

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility');
        $em->flush();
        $this->builder->buildCache();
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

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility');
        $em->flush();
        $this->builder->buildCache();
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

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility');
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
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
        $qb = $em->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->createQueryBuilder('accountCategoryVisibilityResolved');
        $entity = $qb->select('accountCategoryVisibilityResolved', 'accountCategoryVisibility')
            ->leftJoin('accountCategoryVisibilityResolved.sourceCategoryVisibility', 'accountCategoryVisibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('accountCategoryVisibilityResolved.scope', ':scope')
                )
            )
            ->setParameters([
                'category' => $this->category,
                'scope' => $this->scope,
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
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
            ->findOneBy(['category' => $this->category, 'scope' => $this->scope]);
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
        $this->assertEquals($this->scope->getId(), $categoryVisibilityResolved['scope_id']);
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
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group1',
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group2',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'accountGroup' => 'account_group.group2',
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3',
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'accountGroup' => 'account_group.group3',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3',
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3',
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => AccountGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'accountGroup' => 'account_group.group3',
                    ],
                ],
            ],
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
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'IDENTITY(scope.accountGroup) as accountGroup',
                'entity.visibility',
                'entity.source'
            )
            ->join('entity.scope', 'scope')
            ->getQuery()
            ->getArrayResult();
    }
}
