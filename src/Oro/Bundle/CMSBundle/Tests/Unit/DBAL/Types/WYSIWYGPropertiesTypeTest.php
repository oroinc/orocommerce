<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;

class WYSIWYGPropertiesTypeTest extends \PHPUnit\Framework\TestCase
{
    private WYSIWYGPropertiesType $type;

    protected function setUp(): void
    {
        $this->type = new WYSIWYGPropertiesType();
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
        $this->assertTrue($this->type->requiresSQLCommentHint($this->createMock(AbstractPlatform::class)));
    }
}
