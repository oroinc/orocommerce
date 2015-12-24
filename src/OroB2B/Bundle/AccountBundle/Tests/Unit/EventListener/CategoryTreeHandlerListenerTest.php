<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\EventListener\CategoryTreeHandlerListener;
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
        $this->markTestSkipped('Should be fixed in scope of BB-1800');

        $this->categoryVisibilityResolver = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryTreeHandlerListener($this->categoryVisibilityResolver);
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
     * @param array $visibleCategoryIds
     * @param UserInterface|null $user
     */
    public function testOnCreateAfter(
        array $categories,
        array $expected,
        array $visibleCategoryIds,
        UserInterface $user = null
    ) {
        $categories = $this->prepareCategories($categories);
        $expected = $this->prepareCategories($expected);
        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);

        if ($user instanceof User) {
            $this->categoryVisibilityResolver->expects($this->never())
                ->method('isCategoryVisibleForAccount');
        } else {
            $index = 0;
            foreach ($categories as $category) {
                $this->categoryVisibilityResolver->expects($this->at($index))
                    ->method('isCategoryVisibleForAccount')
                    ->with($category, $user instanceof AccountUser ? $user->getAccount() : null)
                    ->willReturn(in_array($category->getId(), $visibleCategoryIds));
                ++$index;
            }
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
                'visibleCategoryIds' => [],
                'user' => new User()
            ],
            'tree for anonymous user with visible ids' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                ],
                'visibleCategoryIds' => [1, 2, 6, 7, 12, 13, 14, 15],
                'user' => null
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
                'visibleCategoryIds' => [1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
                'user' => (new AccountUser)
                    ->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 42]))
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
