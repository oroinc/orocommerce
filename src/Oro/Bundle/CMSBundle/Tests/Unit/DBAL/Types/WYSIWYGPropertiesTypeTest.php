<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGPropertiesTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Type */
    private $type;

    public static function setUpBeforeClass(): void
    {
        Type::addType('wysiwyg_properties', WYSIWYGPropertiesType::class);
    }

    protected function setUp(): void
    {
        $this->type = Type::getType('wysiwyg_properties');
    }

    public function testSuffixConst(): void
    {
        $this->assertEquals('_properties', WYSIWYGPropertiesType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        $this->assertEquals('wysiwyg_properties', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}
