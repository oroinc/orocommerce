<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryFormTemplateDataProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class CategoryFormTemplateDataProviderTest extends TestCase
{
    use EntityTrait;

    private SortOrderDialogTargetStorage|MockObject $sortOrderDialogTargetStorage;

    private CategoryFormTemplateDataProvider $provider;

    protected function setUp(): void
    {
        $this->sortOrderDialogTargetStorage = $this->createMock(SortOrderDialogTargetStorage::class);

        $this->provider = new CategoryFormTemplateDataProvider($this->sortOrderDialogTargetStorage);
    }

    public function testGetDataWhenWrongEntity(): void
    {
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $form
            ->expects(self::never())
            ->method('createView');

        $this->expectException(\InvalidArgumentException::class);

        $this->provider->getData(new \stdClass(), $form, $request);
    }

    public function testGetDataWhenSubmitted(): void
    {
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndNoTarget(): void
    {
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('hasTarget')
            ->with(Category::class, $entity->getId())
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedHasTarget(): void
    {
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);


        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('hasTarget')
            ->with(Category::class, $entity->getId())
            ->willReturn(true);

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('removeTarget')
            ->with(Category::class, $entity->getId())
            ->willReturn(true);

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => true,
        ], $result);
    }
}
