<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Normalizer\CategoryNormalizer;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;

class CategoryNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CategoryNormalizer($this->createMock(FieldHelper::class));
    }

    public function testSupportsNormalizationWhenCategory(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(new Category()));
    }

    public function testSupportsNormalizationWhenNotCategory(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testSupportsDenormalizationWhenCategory(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization([], Category::class));
    }

    public function testSupportsDenormalizationWhenNotCategory(): void
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], \stdClass::class));
    }
}
