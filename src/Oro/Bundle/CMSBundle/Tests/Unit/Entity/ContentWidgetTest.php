<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentWidgetTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(
            new ContentWidget(),
            [
                ['id', 42],
                ['name', 'test_name'],
                ['description', 'this is test description'],
                ['createdAt', new \DateTime('now')],
                ['updatedAt', new \DateTime('now')],
                ['organization', new Organization()],
                ['widgetType', 'test_type'],
                ['layout', 'test_layout'],
                ['settings', ['param' => 'value']],
            ]
        );
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(?string $layout, string $expectedResult): void
    {
        $contentWidget = (new ContentWidget())
            ->setName('test_name')
            ->setLayout($layout);

        self::assertEquals($expectedResult, $contentWidget->toString());
    }

    public function toStringDataProvider(): array
    {
        return [
            'no layout' => [
                'layout' => null,
                'expectedResult' => 'name:test_name, layout:',
            ],
            'empty layout' => [
                'layout' => '',
                'expectedResult' => 'name:test_name, layout:',
            ],
            'layout' => [
                'layout' => 'layout_name',
                'expectedResult' => 'name:test_name, layout:layout_name',
            ],
        ];
    }

    /**
     * @dataProvider getHashDataProvider
     */
    public function testGetHash(?string $layout, string $expectedResult): void
    {
        $contentWidget = (new ContentWidget())
            ->setName('test_name')
            ->setLayout($layout);

        self::assertEquals($expectedResult, $contentWidget->getHash());
    }

    public function getHashDataProvider(): array
    {
        return [
            'no layout' => [
                'layout' => null,
                'expectedResult' => '154d353f9f9fbe6b5ad2325ebb386f41',
            ],
            'empty layout' => [
                'layout' => '',
                'expectedResult' => '154d353f9f9fbe6b5ad2325ebb386f41',
            ],
            'layout' => [
                'layout' => 'layout_name',
                'expectedResult' => '177ef02cfa8ff573b2182abf72713776',
            ],
        ];
    }
}
