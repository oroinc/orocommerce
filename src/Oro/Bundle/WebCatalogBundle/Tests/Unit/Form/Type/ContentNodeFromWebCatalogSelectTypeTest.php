<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeFromWebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;

class ContentNodeFromWebCatalogSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentNodeFromWebCatalogSelectType
     */
    protected $formType;

    /**
     * @var ContentNodeTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $treeHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->treeHandler = $this->createMock(ContentNodeTreeHandler::class);

        $this->formType = new ContentNodeFromWebCatalogSelectType($this->treeHandler);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_web_catalog_content_node_from_web_catalog_select', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityTreeSelectType::class, $this->formType->getParent());
    }
}
