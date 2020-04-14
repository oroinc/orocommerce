<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\ContentNodeFormTemplateDataProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class ContentNodeFormTemplateDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentNodeFormTemplateDataProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ContentNodeFormTemplateDataProvider();
    }

    public function testGetDataWhenWrongEntity()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $form->expects($this->never())
            ->method('createView');
        $this->expectException(\InvalidArgumentException::class);
        $this->provider->getData(new \stdClass(), $form, $request);
    }

    public function testGetDataWhenNotSubmitted()
    {
        $entity = new ContentNode();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $formView = new FormView();
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWhenIsValid()
    {
        $entity = new ContentNode();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $formView = new FormView();
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $result = $this->provider->getData($entity, $form, $request);
        self::assertArrayNotHasKey('expandedContentVariantForms', $result);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertArrayHasKey('form', $result);
        self::assertEquals($formView, $result['form']);
    }

    public function testGetDataWithoutExpandedForms()
    {
        $entity = new ContentNode();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();

        $contentVariantFormView = new FormView();
        $contentVariantFormView->vars['value'] = new ContentVariant();
        $collectionFormView = new FormView();
        $collectionFormView->children[] = $contentVariantFormView;
        $formView = new FormView();
        $formView->children['contentVariants'] = $collectionFormView;
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
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

    public function testGetData()
    {
        $entity = new ContentNode();
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
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
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
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
}
