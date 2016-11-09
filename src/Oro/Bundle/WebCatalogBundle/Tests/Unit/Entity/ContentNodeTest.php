<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentNodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new ContentNode(), [
            ['parentNode', new ContentNode()],
            ['webCatalog', new WebCatalog()],
            ['name', 'Node name'],
            ['materializedPath', 'path/to/node'],
            ['left', 30],
            ['level', 42],
            ['right', 20],
            ['root', 1],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()]
        ]);
        $this->assertPropertyCollections(new ContentNode(), [
            ['childNodes', new ContentNode()],
            ['titles', new LocalizedFallbackValue()],
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['slugs', new Slug()],
            ['contentVariants', new ContentVariant()],
        ]);
    }

    public function testIsUpdatedAtSet()
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(new \DateTime());

        $this->assertTrue($entity->isUpdatedAtSet());
    }

    public function testIsUpdatedAtNotSet()
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(null);

        $this->assertFalse($entity->isUpdatedAtSet());
    }
}
