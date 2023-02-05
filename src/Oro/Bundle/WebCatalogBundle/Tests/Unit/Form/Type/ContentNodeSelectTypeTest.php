<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeSelectType;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ContentNodeSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ContentNodeTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $treeHandler;

    /** @var EntityTreeSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->treeHandler = $this->createMock(ContentNodeTreeHandler::class);
        $this->formType = new ContentNodeSelectType($this->treeHandler);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityIdentifierType::class => new EntityTypeStub([
                        1 => $this->getEntity(ContentNode::class, ['id' => 1])
                    ])
                ],
                []
            )
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ContentNodeSelectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityTreeSelectType::class, $this->formType->getParent());
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
}
