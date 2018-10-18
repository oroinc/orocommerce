<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TextContentVariantTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new TextContentVariant(), [
            ['contentBlock', new ContentBlock()],
            ['content', 'Test Content'],
            ['default', true],
        ]);

        $this->assertPropertyCollections(new TextContentVariant(), [
            ['scopes', new Scope()],
        ]);
    }
}
