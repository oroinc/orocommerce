<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Form\Type\ContentNodeSelectType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;

class ContentNodeSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentNodeSelectType
     */
    protected $formType;

    /**
     * @var ContentNodeTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $treeHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->treeHandler = $this->createMock(ContentNodeTreeHandler::class);

        $this->formType = new ContentNodeSelectType($this->treeHandler);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_consent_web_catalog_content_node_select', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityTreeSelectType::class, $this->formType->getParent());
    }
}
