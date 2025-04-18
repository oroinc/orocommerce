<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Form\Type;

use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeFromWebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\EmptySearchResultPageSelectSystemConfigType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantScopes;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Symfony\Component\Form\FormFactoryInterface;

class EmptySearchResultPageSelectSystemConfigTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testCanBeCreatedWithNoInitialData(): void
    {
        $form = $this->formFactory->create(EmptySearchResultPageSelectSystemConfigType::class);

        self::assertNull($form->getData());
    }

    public function testCanBeCreatedWithInitialData(): void
    {
        $contentNode = new ContentNode();
        $form = $this->formFactory->create(EmptySearchResultPageSelectSystemConfigType::class, $contentNode);

        self::assertSame($contentNode, $form->getData());
    }

    public function testHasFields(): void
    {
        $form = $this->formFactory->create(EmptySearchResultPageSelectSystemConfigType::class);

        self::assertArrayIntersectEquals(
            ['data_class' => null, 'error_bubbling' => false],
            $form->getConfig()->getOptions()
        );

        self::assertFormHasField($form, 'webCatalog', WebCatalogSelectType::class, [
            'label' => false,
            'required' => false,
            'create_enabled' => false,
        ]);

        self::assertFormHasField($form, 'contentNode', ContentNodeFromWebCatalogSelectType::class, [
            'label' => false,
            'required' => true,
        ]);
    }

    public function testWebCatalogIsSetFromContentNode(): void
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $form = $this->formFactory->create(EmptySearchResultPageSelectSystemConfigType::class, $contentNode);

        self::assertSame($webCatalog, $form->get('webCatalog')->getData());
    }

    public function testHasViewVars(): void
    {
        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            null,
            ['csrf_protection' => false]
        );

        $formView = $form->createView();

        self::assertArrayHasKey('data-page-component-module', $formView->vars['attr']);
        self::assertEquals(
            'oroui/js/app/components/view-component',
            $formView->vars['attr']['data-page-component-module']
        );

        self::assertArrayHasKey('data-page-component-options', $formView->vars['attr']);
        $pageComponentOptions = json_decode($formView->vars['attr']['data-page-component-options'], true);

        self::assertEquals(
            [
                'view' => 'orowebcatalog/js/app/views/content-node-from-webcatalog-view',
                'listenedFieldName' => $formView['webCatalog']->vars['full_name'],
                'triggeredFieldName' => $formView['contentNode']->vars['full_name'],
            ],
            $pageComponentOptions
        );
    }

    public function testSubmitWithEmptyDataWhenNoInitialData(): void
    {
        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            null,
            ['csrf_protection' => false]
        );

        $form->submit([]);

        self::assertTrue($form->isValid(), $form->getErrors(true, true));
        self::assertTrue($form->isSynchronized());

        self::assertNull($form->getData());
    }

    public function testSubmitWithEmptyDataWhenHasInitialData(): void
    {
        $this->loadFixtures([LoadContentNodesData::class]);

        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);

        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            $contentNode,
            ['csrf_protection' => false]
        );

        $form->submit([]);

        self::assertTrue($form->isValid(), $form->getErrors(true, true));
        self::assertTrue($form->isSynchronized());

        self::assertNull($form->getData());
    }

    public function testSubmitWithNonEmptyDataWhenNoInitialData(): void
    {
        $this->loadFixtures([LoadContentVariantsData::class, LoadContentVariantScopes::class]);

        $newContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            null,
            ['csrf_protection' => false]
        );

        $form->submit(['contentNode' => $newContentNode->getId()]);

        self::assertTrue($form->isValid(), $form->getErrors(true, true));
        self::assertTrue($form->isSynchronized());

        self::assertSame($newContentNode, $form->getData());
    }

    public function testSubmitWithNonEmptyDataWhenHasInitialData(): void
    {
        $this->loadFixtures([LoadContentVariantsData::class, LoadContentVariantScopes::class]);

        $initialContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $newContentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            $initialContentNode,
            ['csrf_protection' => false]
        );

        $form->submit(['contentNode' => $newContentNode->getId()]);

        self::assertTrue($form->isValid(), $form->getErrors(true, true));
        self::assertTrue($form->isSynchronized());

        self::assertSame($newContentNode, $form->getData());
    }

    public function testSubmitWithInvalidData(): void
    {
        $form = $this->formFactory->create(
            EmptySearchResultPageSelectSystemConfigType::class,
            null,
            ['csrf_protection' => false]
        );

        $form->submit(['contentNode' => self::BIGINT]);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertNull($form->getData());
    }
}
