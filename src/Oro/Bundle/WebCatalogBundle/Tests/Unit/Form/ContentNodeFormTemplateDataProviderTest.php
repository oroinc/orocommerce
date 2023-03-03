<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTriggerFormHandlerEventListener;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\ContentNodeFormTemplateDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ContentNodeFormTemplateDataProviderTest extends TestCase
{
    private ContentNodeFormTemplateDataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ContentNodeFormTemplateDataProvider();
    }

    public function testGetDataWhenWrongEntity(): void
    {
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $form->expects(self::never())
            ->method('createView');
        $this->expectException(InvalidArgumentException::class);
        $this->provider->getData(new \stdClass(), $form, $request);
    }

    public function testGetDataWhenNotSubmitted(): void
    {
        $entity = new ContentNode();
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenIsValid(): void
    {
        $entity = new ContentNode();
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWithoutExpandedForms(): void
    {
        $entity = new ContentNode();
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayHasKey('expandedContentVariantForms', $result);
        self::assertEquals([], $result['expandedContentVariantForms']);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetData(): void
    {
        $entity = new ContentNode();
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariant = new ContentVariant();
        $contentVariant->setExpanded(true);
        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = $contentVariant;
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayHasKey('expandedContentVariantForms', $result);
        self::assertEquals([$contentVariantFormView], $result['expandedContentVariantForms']);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenNotSubmittedButNoSession(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

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
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithoutTarget(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

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
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithAnotherTarget(): void
    {
        $targetName = 'sample_form_name';
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['full_name'] = $targetName;
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

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
            ->willReturn('sample_another_form_name');

        $session
            ->expects(self::never())
            ->method('remove');

        $result = $this->provider->getData($entity, $form, $request);

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'expandedContentVariantForms' => [],
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndSessionExistsWithSameTarget(): void
    {
        $targetName = 'sample_form_name';
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['full_name'] = $targetName;
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;

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
            'expandedContentVariantForms' => [$contentVariantFormView],
        ], $result);

        self::assertArrayHasKey('triggerSortOrderDialog', $contentVariantFormView->vars);
        self::assertEquals(true, $contentVariantFormView->vars['triggerSortOrderDialog']);
    }
}
