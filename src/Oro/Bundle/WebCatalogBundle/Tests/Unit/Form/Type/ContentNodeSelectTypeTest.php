<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeSelectType;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as EntityIdentifierTypeStub;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class EntityTreeSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $treeHandler;

    /**
     * @var EntityTreeSelectType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->treeHandler = $this->getMockBuilder(ContentNodeTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new ContentNodeSelectType($this->treeHandler);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierTypeStub(
            [
                1 => $this->getEntity(ContentNode::class, ['id' => 1])
            ]
        );

        return [
            new PreloadedExtension(
                [
                    EntityIdentifierType::NAME => $entityIdentifierType,
                ],
                []
            )
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ContentNodeSelectType::NAME, $this->formType->getName());
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

        $form = $this->factory->create($this->formType, null, ['web_catalog' => $webCatalog, 'tree_key' => 'test']);
        $form->submit(null);
        $view = $form->createView();

        $this->assertArrayHasKey('treeOptions', $view->vars);
        $this->assertArrayHasKey('data', $view->vars['treeOptions']);
        $this->assertEquals($treeData, $view->vars['treeOptions']['data']);
    }
}
