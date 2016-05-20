<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\EventListener\CategoryTreeHandlerListener;
use OroB2B\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryTreeHandlerListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CategoryVisibilityResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryVisibilityResolver;
    
    /**
     * @var AccountUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountUserRelationsProvider;

    /**
     * @var CategoryTreeHandlerListener
     */
    protected $listener;

    /** @var array */
    protected static $categories = [
        ['id' => 1, 'parent' => null],
        ['id' => 2, 'parent' => 1],
        ['id' => 3, 'parent' => 1],
        ['id' => 4, 'parent' => 2],
        ['id' => 5, 'parent' => 2],
        ['id' => 6, 'parent' => 4],
        ['id' => 7, 'parent' => 4],
        ['id' => 8, 'parent' => 5],
        ['id' => 9, 'parent' => 5],
        ['id' => 10, 'parent' => 3],
        ['id' => 11, 'parent' => 3],
        ['id' => 12, 'parent' => 10],
        ['id' => 13, 'parent' => 10],
        ['id' => 14, 'parent' => 11],
        ['id' => 15, 'parent' => 11],
    ];

    protected function setUp()
    {
        $this->categoryVisibilityResolver = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountUserRelationsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Provider\AccountUserRelationsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryTreeHandlerListener(
            $this->categoryVisibilityResolver,
            $this->accountUserRelationsProvider
        );
    }

    protected function tearDown()
    {
        unset($this->categoryVisibilityResolver, $this->listener);
    }

    /**
     * @dataProvider onCreateAfterDataProvider
     *
     * @param array $categories
     * @param array $expected
     * @param array $hiddenCategoryIds
     * @param UserInterface|null $user
     * @param Account $account
     * @param AccountGroup|null $accountGroup
     */
    public function testOnCreateAfter(
        array $categories,
        array $expected,
        array $hiddenCategoryIds,
        UserInterface $user = null,
        Account $account = null,
        AccountGroup $accountGroup = null
    ) {
        $categories = $this->prepareCategories($categories);
        $expected = $this->prepareCategories($expected);
        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);

        if (!$user) {
            $this->accountUserRelationsProvider->expects($this->once())
                ->method('getAccountGroup')
                ->with($user)
                ->willReturn($accountGroup);
        }

        if ($user instanceof User) {
            $this->categoryVisibilityResolver->expects($this->never())
                ->method('isCategoryVisibleForAccount');
            $this->categoryVisibilityResolver->expects($this->never())
                ->method('isCategoryVisibleForAccountGroup');
            $this->categoryVisibilityResolver->expects($this->never())
                ->method('getHiddenCategoryIds');
        } elseif ($user instanceof AccountUser && $account) {
            $this->accountUserRelationsProvider->expects($this->once())
                ->method('getAccount')
                ->with($user)
                ->willReturn($account);
            $this->categoryVisibilityResolver->expects($this->once())
                ->method('getHiddenCategoryIdsForAccount')
                ->with($account)
                ->willReturn($hiddenCategoryIds);
        } elseif (!$user && $accountGroup) {
            $this->categoryVisibilityResolver->expects($this->once())
                ->method('getHiddenCategoryIdsForAccountGroup')
                ->with($accountGroup)
                ->willReturn($hiddenCategoryIds);
        } else {
            $this->categoryVisibilityResolver->expects($this->once())
                ->method('getHiddenCategoryIds')
                ->willReturn($hiddenCategoryIds);
        }

        $this->listener->onCreateAfter($event);
        $actual = $event->getCategories();
        $this->assertEquals(count($expected), count($actual));

        foreach ($actual as $id => $category) {
            $this->assertEquals($expected[$id]->getId(), $category->getId());
        }
    }

    /**
     * @return array
     */
    public function onCreateAfterDataProvider()
    {
        return [
            'tree for backend user' => [
                'categories' => self::$categories,
                'expected' => self::$categories,
                'hiddenCategoryIds' => [],
                'user' => new User()
            ],
            'tree for anonymous user with visible ids' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                ],
                'hiddenCategoryIds' => [3, 4, 5, 8, 9, 10, 11],
                'user' => null,
                'account' => null,
                'accountGroup' => new AccountGroup()
            ],
            'tree without user and group' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                ],
                'hiddenCategoryIds' => [3, 4, 5, 8, 9, 10, 11],
                'user' => null,
                'account' => null,
                'accountGroup' => null
            ],
            'tree for account user with invisible ids' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                    ['id' => 4, 'parent' => 2],
                    ['id' => 5, 'parent' => 2],
                    ['id' => 6, 'parent' => 4],
                    ['id' => 7, 'parent' => 4],
                    ['id' => 8, 'parent' => 5],
                    ['id' => 9, 'parent' => 5],
                ],
                'hiddenCategoryIds' => [3],
                'user' => new AccountUser(),
                'account' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 42])
            ]
        ];
    }

    /**
     * @param array $categories
     * @return Category[]
     */
    protected function prepareCategories(array $categories)
    {
        /** @var Category[] $categoriesCollection */
        $categoriesCollection = [];
        foreach ($categories as $item) {
            /** @var Category $category */
            $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => $item['id']]);

            $category->setParentCategory($this->getParent($item['parent'], $categoriesCollection));
            $categoriesCollection[$category->getId()] = $category;
        }
        foreach ($categoriesCollection as $parentCategory) {
            foreach ($categoriesCollection as $category) {
                if ($category->getParentCategory() === $parentCategory) {
                    $parentCategory->addChildCategory($category);
                }
            }
        }

        return $categoriesCollection;
    }

    /**
     * @param int $id
     * @param Category[] $categoriesCollection
     * @return null
     */
    protected function getParent($id, $categoriesCollection)
    {
        $parent = null;
        foreach ($categoriesCollection as $category) {
            if ($category->getId() === $id) {
                $parent = $category;
            }
        }

        return $parent;
    }
}
