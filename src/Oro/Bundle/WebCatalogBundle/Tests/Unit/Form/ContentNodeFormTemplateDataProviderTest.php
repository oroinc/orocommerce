<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\ContentNodeFormTemplateDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class ContentNodeFormTemplateDataProviderTest extends TestCase
{
    private SortOrderDialogTargetStorage|MockObject $sortOrderDialogTargetStorage;

    private ContentNodeFormTemplateDataProvider $provider;

    protected function setUp(): void
    {
        $this->sortOrderDialogTargetStorage = $this->createMock(SortOrderDialogTargetStorage::class);

        $this->provider = new ContentNodeFormTemplateDataProvider($this->sortOrderDialogTargetStorage);
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
        $form = $this->createMock(FormInterface::class);
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

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenIsValid(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
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

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenSubmittedAndNotValidWithoutExpandedForms(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
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

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));
        self::assertArrayHasKey('expandedContentVariantForms', $result);
        self::assertEquals([], $result['expandedContentVariantForms']);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenSubmittedAndNotValidWithExpandedForms(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
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

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));
        self::assertArrayHasKey('expandedContentVariantForms', $result);
        self::assertEquals([$contentVariantFormView], $result['expandedContentVariantForms']);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenNotSubmittedAndNoTarget(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $contentVariantFormView = new FormView();
        $contentVariant = (new ContentVariantStub())->setId(442);
        $contentVariantFormView->vars['value'] = $contentVariant;
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

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('hasTarget')
            ->with(ContentVariant::class, $contentVariant->getId())
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
        ], $result);
    }

    public function testGetDataWhenNotSubmittedAndHasTarget(): void
    {
        $entity = new ContentNode();
        $form = $this->createMock(FormInterface::class);
        $contentVariantFormView = new FormView();
        $contentVariant = (new ContentVariantStub())->setId(442);
        $contentVariantFormView->vars['value'] = $contentVariant;
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

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('hasTarget')
            ->with(ContentVariant::class, $contentVariant->getId())
            ->willReturn(true);

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('removeTarget')
            ->with(ContentVariant::class, $contentVariant->getId())
            ->willReturn(true);

        $result = $this->provider->getData($entity, $form, $this->createMock(Request::class));

        self::assertEquals([
            'entity' => $entity,
            'form' => $formView,
            'expandedContentVariantForms' => [$contentVariantFormView],
        ], $result);

        self::assertArrayHasKey('triggerSortOrderDialog', $contentVariantFormView->vars);
        self::assertEquals(true, $contentVariantFormView->vars['triggerSortOrderDialog']);
    }
}
