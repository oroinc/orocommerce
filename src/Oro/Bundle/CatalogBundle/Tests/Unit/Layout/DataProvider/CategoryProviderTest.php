<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject $requestProductHandler;

    private CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $categoryRepository;

    private CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject $categoryTreeProvider;

    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    private MasterCatalogRootProviderInterface|\PHPUnit\Framework\MockObject\MockObject $masterCatalogProvider;

    private CategoryProvider $categoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->masterCatalogProvider = $this->createMock(MasterCatalogRootProviderInterface::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoryProvider = new CategoryProvider(
            $this->requestProductHandler,
            $registry,
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $this->masterCatalogProvider
        );
    }

    public function testGetCurrentCategoryUsingMasterCatalogRoot(): void
    {
        $category = new Category();

        $this->requestProductHandler
            ->expects(self::once())
            ->method('getCategoryId')
            ->willReturn(0);

        $this->masterCatalogProvider
            ->expects(self::once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        self::assertSame($category, $result);
    }

    public function testGetCurrentCategoryUsingFind(): void
    {
        $category = new Category();
        $categoryId = 1;

        $this->requestProductHandler
            ->expects(self::once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->categoryRepository
            ->expects(self::once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        self::assertSame($category, $result);
    }

    public function testGetIncludeSubcategoriesChoice(): void
    {
        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturnOnConsecutiveCalls(true, false);
        self::assertEquals(true, $this->categoryProvider->getIncludeSubcategoriesChoice());
        self::assertEquals(false, $this->categoryProvider->getIncludeSubcategoriesChoice());
    }

    /**
     * @dataProvider getUserDataProvider
     */
    public function testGetCategoryPath(?UserInterface $userFromToken, ?UserInterface $expectedUser): void
    {
        $this->mockTokenAccessor($userFromToken);

        $categoryAId = 1;
        $categoryA = new CategoryStub();
        $categoryA->setId($categoryAId);

        $categoryB = new CategoryStub();
        $categoryB->setId(2);

        $this->requestProductHandler
            ->expects(self::once())
            ->method('getCategoryId')
            ->willReturn($categoryAId);

        $this->categoryRepository
            ->expects(self::once())
            ->method('find')
            ->with($categoryAId)
            ->willReturn($categoryA);

        $parentCategories = [
            $categoryA,
            $categoryB,
        ];
        $this->categoryTreeProvider->expects(self::once())
            ->method('getParentCategories')
            ->with($expectedUser, $categoryA)
            ->willReturn($parentCategories);

        self::assertSame(
            $parentCategories,
            $this->categoryProvider->getCategoryPath()
        );
    }

    public function getUserDataProvider(): array
    {
        $customerUser = new CustomerUserStub(1);

        return [
            'null' => [
                'userFromToken' => null,
                'expectedUser' => null,
            ],
            'not customer user' => [
                'userFromToken' => $this->createMock(UserInterface::class),
                'expectedUser' => null,
            ],
            'customer user' => [
                'userFromToken' => $customerUser,
                'expectedUser' => $customerUser,
            ],
        ];
    }

    private function mockTokenAccessor(?UserInterface $user): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
    }
}
