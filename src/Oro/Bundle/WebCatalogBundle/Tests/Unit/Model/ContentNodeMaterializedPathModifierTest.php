<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeMaterializedPathModifierTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->modifier = new ContentNodeMaterializedPathModifier($this->registry);
    }

    public function testCalculateMaterializedPath()
    {
        $parentNode = new ContentNode();
        $parentNode->setMaterializedPath('1_2');

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $node->setParentNode($parentNode);

        $actual = $this->modifier->calculateMaterializedPath($node);
        $this->assertEquals('1_2_3', $actual->getMaterializedPath());
    }

    public function calculateChildrenMaterializedPath()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        $node->setParentNode($parentNode);

        $childNodes = $this->modifier->calculateChildrenMaterializedPath($parentNode);
        $actual = $childNodes[0];

        $this->assertEquals('1_2', $actual->getMaterializedPath());
    }
}
