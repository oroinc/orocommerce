<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTriggerFormHandlerEventListener;
use Oro\Bundle\CatalogBundle\Provider\CategoryFormTemplateDataProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CategoryFormTemplateDataProviderTest extends TestCase
{
    use EntityTrait;

    private CategoryFormTemplateDataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new CategoryFormTemplateDataProvider();
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

    public function testGetDataWhenNotSubmittedButNoSession(): void
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
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithoutTarget(): void
    {
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $session
            ->expects(self::once())
            ->method('get')
            ->with(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET)
            ->willReturn('');

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithAnotherTarget(): void
    {
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $session
            ->expects(self::once())
            ->method('get')
            ->with(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET)
            ->willReturn('sample_form_name');

        $session
            ->expects(self::never())
            ->method('remove');

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithSameTarget(): void
    {
        $targetName = 'sample_form_name';
        $entity = new CategoryStub(42);
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn($targetName);

        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $formView = new FormView();
        $form
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $session
            ->expects(self::once())
            ->method('get')
            ->with(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET)
            ->willReturn($targetName);

        $session
            ->expects(self::once())
            ->method('remove')
            ->with(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET);

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => true,
        ], $result);
    }
}
