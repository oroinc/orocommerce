<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;

class WYSIWYGStyleTypeTest extends \PHPUnit\Framework\TestCase
{
    private WYSIWYGStyleType $type;

    protected function setUp(): void
    {
        $this->type = new WYSIWYGStyleType();
    }

    public function testPrefixConst(): void
    {
        self::assertEquals('_style', WYSIWYGStyleType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        self::assertEquals('wysiwyg_style', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        self::assertTrue($this->type->requiresSQLCommentHint($this->createMock(AbstractPlatform::class)));
    }
}
