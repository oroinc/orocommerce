<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;

class WYSIWYGTypeTest extends \PHPUnit\Framework\TestCase
{
    private WYSIWYGType $type;

    protected function setUp(): void
    {
        $this->type = new WYSIWYGType();
    }

    public function testGetName(): void
    {
        $this->assertEquals('wysiwyg', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->createMock(AbstractPlatform::class)));
    }
}
