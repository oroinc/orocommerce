<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalogNode;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalogPage;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WebCatalogNodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new WebCatalogNode(), [
            ['parentNode', new WebCatalogNode()],
            ['name', 'Node name'],
            ['materializedPath', 'path/to/node'],
            ['left', 30],
            ['level', 42],
            ['right', 20],
            ['root', 1],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()]
        ]);
        $this->assertPropertyCollections(new WebCatalogNode(), [
            ['childNodes', new WebCatalogNode()],
            ['titles', new LocalizedFallbackValue()],
            ['slugs', new LocalizedFallbackValue()],
            ['pageSlugs', new Slug()],
            ['pages', new WebCatalogPage()],
        ]);
    }

    public function testIsUpdatedAtSet()
    {
        $entity = new WebCatalogNode();
        $entity->setUpdatedAt(new \DateTime());

        $this->assertTrue($entity->isUpdatedAtSet());
    }

    public function testIsUpdatedAtNotSet()
    {
        $entity = new WebCatalogNode();
        $entity->setUpdatedAt(null);

        $this->assertFalse($entity->isUpdatedAtSet());
    }
}
