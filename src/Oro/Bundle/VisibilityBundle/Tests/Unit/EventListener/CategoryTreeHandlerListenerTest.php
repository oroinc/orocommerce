<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\VisibilityBundle\EventListener\CategoryTreeHandlerListener;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\CategoryVisibilityProvider;
use Oro\Component\Testing\ReflectionUtil;

class CategoryTreeHandlerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryVisibilityProvider;

    /** @var CategoryTreeHandlerListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->categoryVisibilityProvider = $this->createMock(CategoryVisibilityProvider::class);

        $this->listener = new CategoryTreeHandlerListener($this->categoryVisibilityProvider);
    }

    private function getCategory(int $id): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    public function testForBackendUser(): void
    {
        $categories = [
            $this->getCategory(1),
            $this->getCategory(2)
        ];
        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser(new User());

        $this->categoryVisibilityProvider->expects($this->never())
            ->method('getHiddenCategoryIds');

        $this->listener->onCreateAfter($event);

        self::assertSame($categories, $event->getCategories());
    }

    /**
     * @dataProvider notBackendUserDataProvider
     */
    public function testForNotBackendUser(?CustomerUser $user): void
    {
        $categories = [
            $this->getCategory(1),
            $this->getCategory(2),
            $this->getCategory(3),
            $this->getCategory(4)
        ];

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);

        $this->categoryVisibilityProvider->expects($this->once())
            ->method('getHiddenCategoryIds')
            ->with($this->identicalTo($user))
            ->willReturn([2, 4]);

        $this->listener->onCreateAfter($event);

        self::assertSame([$categories[0], $categories[2]], $event->getCategories());
    }

    public function notBackendUserDataProvider(): array
    {
        return [[null], [new CustomerUser()]];
    }
}
