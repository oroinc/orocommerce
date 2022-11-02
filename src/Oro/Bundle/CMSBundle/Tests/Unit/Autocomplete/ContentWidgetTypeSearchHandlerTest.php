<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\CMSBundle\Autocomplete\ContentWidgetTypeSearchHandler;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContentWidgetTypeSearchHandler */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ContentWidgetTypeRegistry::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($key) {
                    return $key . '.trans';
                }
            );

        $this->searchHandler = new ContentWidgetTypeSearchHandler(
            $this->registry,
            $this->translator,
            new PropertyAccessor()
        );
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(string $query, int $page, int $perPage, bool $searchById, bool $expected): void
    {
        $type = new ContentWidgetTypeStub();

        $this->registry->expects($this->once())
            ->method('getTypes')
            ->willReturn([$type]);

        $results = [
            'results' => $expected ? [['id' => $type::getName(), 'label' => $type->getLabel() . '.trans']] : [],
            'more' => false
        ];

        $this->assertEquals($results, $this->searchHandler->search($query, $page, $perPage, $searchById));
    }

    public function searchDataProvider(): array
    {
        return [
            [
                'query' => '',
                'page' => 1,
                'perPage' => 25,
                'searchById' => false,
                'expected' => true
            ],
            [
                'query' => 'unknown',
                'page' => 1,
                'perPage' => 25,
                'searchById' => false,
                'expected' => false
            ],
            [
                'query' => 'stub',
                'page' => 1,
                'perPage' => 25,
                'searchById' => false,
                'expected' => true
            ],
            [
                'query' => 'stub',
                'page' => 1,
                'perPage' => 25,
                'searchById' => true,
                'expected' => false
            ],
            [
                'query' => ContentWidgetTypeStub::getName(),
                'page' => 1,
                'perPage' => 25,
                'searchById' => true,
                'expected' => true
            ],
            [
                'query' => ContentWidgetTypeStub::getName(),
                'page' => 2,
                'perPage' => 25,
                'searchById' => true,
                'expected' => false
            ]
        ];
    }

    public function testGetProperties(): void
    {
        $this->assertEquals(['label'], $this->searchHandler->getProperties());
    }

    public function testGetEntityName(): void
    {
        $this->assertEquals(ContentWidgetTypeInterface::class, $this->searchHandler->getEntityName());
    }

    public function testConvertItem(): void
    {
        $type = new ContentWidgetTypeStub();

        $this->assertEquals(
            ['id' => $type::getName(), 'label' => $type->getLabel() . '.trans'],
            $this->searchHandler->convertItem($type)
        );
    }
}
