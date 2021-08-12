<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGStyleTypeTest extends \PHPUnit\Framework\TestCase
{
    private Type $type;

    public static function setUpBeforeClass(): void
    {
        Type::addType('wysiwyg_style', WYSIWYGStyleType::class);
    }

    protected function setUp(): void
    {
        $this->type = Type::getType('wysiwyg_style');
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
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        self::assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}
