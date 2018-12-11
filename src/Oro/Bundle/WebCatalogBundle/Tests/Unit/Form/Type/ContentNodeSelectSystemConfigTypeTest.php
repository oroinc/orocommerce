<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeSelectSystemConfigType;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeSelectType;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as EntityIdentifierTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ContentNodeSelectSystemConfigTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ContentNodeTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $treeHandler;

    /** @var ContentNodeSelectSystemConfigType */
    private $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formType = new ContentNodeSelectSystemConfigType($this->doctrineHelper, $this->configManager);

        $this->treeHandler = $this->createMock(ContentNodeTreeHandler::class);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ContentNodeSelectSystemConfigType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ContentNodeSelectType::class, $this->formType->getParent());
    }

    public function testOptions()
    {
        $treeData = ['a' => true];

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);
        $root = $this->getEntity(ContentNode::class, ['id' => 1]);

        $this->treeHandler->expects($this->once())
            ->method('getTreeRootByWebCatalog')
            ->with($webCatalog)
            ->willReturn($root);
        $this->treeHandler->expects($this->once())
            ->method('createTree')
            ->with($root, true)
            ->willReturn($treeData);

        $form = $this->factory->create(ContentNodeSelectType::class, null, [
            'web_catalog' => $webCatalog,
            'tree_key' => 'test'
        ]);
        $form->submit(null);
        $view = $form->createView();

        $this->assertArrayHasKey('treeOptions', $view->vars);
        $this->assertArrayHasKey('data', $view->vars['treeOptions']);
        $this->assertEquals($treeData, $view->vars['treeOptions']['data']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $contentNodeSelecType = new ContentNodeSelectType($this->treeHandler);
        $entityIdentifierType = new EntityIdentifierTypeStub([
            1 => $this->getEntity(ContentNode::class, ['id' => 1])
        ]);

        return [
            new PreloadedExtension(
                [
                    ContentNodeSelectSystemConfigType::class => $this->formType,
                    ContentNodeSelectType::class => $contentNodeSelecType,
                    EntityIdentifierType::class => $entityIdentifierType,
                ],
                []
            )
        ];
    }
}
