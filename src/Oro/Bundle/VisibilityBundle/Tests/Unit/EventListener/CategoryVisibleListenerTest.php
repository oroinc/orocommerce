<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\EventListener\CategoryVisibleListener;
use Oro\Bundle\VisibilityBundle\Visibility\Checker\FrontendCategoryVisibilityCheckerInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CategoryVisibleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FrontendCategoryVisibilityCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryVisibilityChecker;

    /** @var CategoryVisibleListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->categoryVisibilityChecker = $this->createMock(FrontendCategoryVisibilityCheckerInterface::class);

        $container = TestContainerBuilder::create()
            ->add(FrontendCategoryVisibilityCheckerInterface::class, $this->categoryVisibilityChecker)
            ->getContainer($this);

        $this->listener = new CategoryVisibleListener($this->doctrine, $container);
    }

    private function getControllerEvent(Request $request): ControllerEvent
    {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            function () {
            },
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testNonProductRoute(): void
    {
        $request = new Request();
        $request->attributes->add(['_route' => 'some_route']);
        $request->query->add(['categoryId' => '45']);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onKernelController($this->getControllerEvent($request));
    }

    public function testRequestWithoutCategoryId(): void
    {
        $request = new Request();
        $request->attributes->add(['_route' => 'oro_product_frontend_product_index']);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onKernelController($this->getControllerEvent($request));
    }

    public function testWhenCategoryNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The category 45 was not found.');

        $request = new Request();
        $request->attributes->add(['_route' => 'oro_product_frontend_product_index']);
        $request->query->add(['categoryId' => '45']);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, self::identicalTo(45))
            ->willReturn(null);

        $this->categoryVisibilityChecker->expects(self::never())
            ->method('isCategoryVisible');

        $this->listener->onKernelController($this->getControllerEvent($request));
    }

    public function testWhenCategoryNotVisible(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The category 45 was not found.');

        $request = new Request();
        $request->attributes->add(['_route' => 'oro_product_frontend_product_index']);
        $request->query->add(['categoryId' => '45']);

        $category = new Category();

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, self::identicalTo(45))
            ->willReturn($category);

        $this->categoryVisibilityChecker->expects(self::once())
            ->method('isCategoryVisible')
            ->with(self::identicalTo($category))
            ->willReturn(false);

        $this->listener->onKernelController($this->getControllerEvent($request));
    }

    public function testWhenCategoryVisible(): void
    {
        $request = new Request();
        $request->attributes->add(['_route' => 'oro_product_frontend_product_index']);
        $request->query->add(['categoryId' => '45']);

        $category = new Category();

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Category::class, self::identicalTo(45))
            ->willReturn($category);

        $this->categoryVisibilityChecker->expects(self::once())
            ->method('isCategoryVisible')
            ->with(self::identicalTo($category))
            ->willReturn(true);

        $this->listener->onKernelController($this->getControllerEvent($request));
    }
}
