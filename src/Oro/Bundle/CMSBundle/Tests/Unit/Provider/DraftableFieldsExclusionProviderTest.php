<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\DraftableFieldsExclusionProvider;

class DraftableFieldsExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getIsSupportDataProvider
     */
    public function testIsSupport(string $className, bool $expectedResult): void
    {
        $provider = new DraftableFieldsExclusionProvider();

        $this->assertEquals($expectedResult, $provider->isSupport($className));
    }

    public function getIsSupportDataProvider(): array
    {
        return [
            'not supported' => [
                'className' => \stdClass::class,
                'expectedResult' => false,
            ],
            'supported' => [
                'className' => Page::class,
                'expectedResult' => true,
            ],
        ];
    }

    public function testGetExcludedFields(): void
    {
        $provider = new DraftableFieldsExclusionProvider();

        $this->assertEquals(['content_style', 'content_properties'], $provider->getExcludedFields());
    }
}
