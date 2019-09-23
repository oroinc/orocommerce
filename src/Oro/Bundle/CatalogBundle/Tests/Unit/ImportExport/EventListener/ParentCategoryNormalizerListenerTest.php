<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\EventListener\ParentCategoryNormalizerListener;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;

class ParentCategoryNormalizerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryImportExportHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryImportExportHelper;

    /** @var ParentCategoryNormalizerListener */
    private $listener;

    protected function setUp()
    {
        $this->categoryImportExportHelper = $this->createMock(CategoryImportExportHelper::class);

        $this->listener = new ParentCategoryNormalizerListener($this->categoryImportExportHelper);
    }

    public function testAfterNormalizeWhenNotCategory(): void
    {
        $event = $this->createMock(NormalizeEntityEvent::class);
        $event
            ->expects($this->once())
            ->method('getObject')
            ->willReturn(new \stdClass());

        $event
            ->expects($this->never())
            ->method('setResultField');

        $this->listener->afterNormalize($event);
    }

    public function testAfterNormalizeWhenNotFullData(): void
    {
        $event = $this->createMock(NormalizeEntityEvent::class);
        $event
            ->expects($this->once())
            ->method('getObject')
            ->willReturn(new Category());

        $event
            ->expects($this->once())
            ->method('isFullData')
            ->willReturn(false);

        $event
            ->expects($this->never())
            ->method('setResultField');

        $this->listener->afterNormalize($event);
    }

    public function testAfterNormalizeWhenNoParentCategory(): void
    {
        $event = new NormalizeEntityEvent(new Category(), [], true);

        $this->listener->afterNormalize($event);

        $this->assertEquals([], $event->getResult());
    }

    /**
     * @dataProvider afterNormalizeDataProvider
     *
     * @param array $result
     * @param string $categoryPath
     * @param array $expectedResult
     */
    public function testAfterNormalize(
        array $result,
        string $categoryPath,
        array $expectedResult
    ): void {
        $category = new Category();
        $category->setParentCategory($parentCategory = new Category());

        $event = new NormalizeEntityEvent($category, $result, true);

        $this->categoryImportExportHelper
            ->expects($this->once())
            ->method('getPersistedCategoryPath')
            ->with($parentCategory)
            ->willReturn($categoryPath);

        $this->listener->afterNormalize($event);

        $this->assertEquals($expectedResult, $event->getResult());
    }

    /**
     * @return array
     */
    public function afterNormalizeDataProvider(): array
    {
        return [
            [
                'result' => [],
                'categoryPath' => 'category 1 / category 2',
                'expectedResult' => [
                    'parentCategory' => ['titles' => ['default' => ['string' => 'category 1 / category 2']]]
                ],
            ],
            [
                'result' => ['parentCategory' => ['id' => 1]],
                'categoryPath' => 'category 1 / category 2',
                'expectedResult' => [
                    'parentCategory' => [
                        'titles' => ['default' => ['string' => 'category 1 / category 2']],
                        'id' => 1,
                    ],
                ],
            ],
        ];
    }
}
