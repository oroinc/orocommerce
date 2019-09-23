<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\EventListener\CategoryHeadersListener;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

class CategoryHeadersListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldHelper */
    private $fieldHelper;

    /** @var CategoryHeadersListener */
    private $listener;

    protected function setUp()
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->listener = new CategoryHeadersListener($this->fieldHelper);
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenNotCategory(): void
    {
        $event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
        $event
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(\stdClass::class);

        $event
            ->expects($this->never())
            ->method('addHeader');

        $event
            ->expects($this->never())
            ->method('setRule');

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenNotFullData(): void
    {
        $event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
        $event
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(Category::class);

        $event
            ->expects($this->once())
            ->method('isFullData')
            ->willReturn(false);

        $event
            ->expects($this->never())
            ->method('addHeader');

        $event
            ->expects($this->never())
            ->method('setRule');

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
    }

    /**
     * @dataProvider afterLoadEntityRulesAndBackendHeadersDataProvider
     *
     * @param array $headers
     * @param array $rules
     * @param $order
     * @param array $expectedHeaders
     * @param array $expectedRules
     */
    public function testAfterLoadEntityRulesAndBackendHeaders(
        array $headers,
        array $rules,
        $order,
        array $expectedHeaders,
        array $expectedRules
    ): void {
        $event = new LoadEntityRulesAndBackendHeadersEvent(Category::class, $headers, $rules, ':', '', true);

        $this->fieldHelper
            ->method('getConfigValue')
            ->with(Category::class, 'parentCategory', 'order')
            ->willReturn($order);

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);

        $this->assertEquals($expectedHeaders, $event->getHeaders());
        $this->assertEquals($expectedRules, $event->getRules());
    }

    /**
     * @return array
     */
    public function afterLoadEntityRulesAndBackendHeadersDataProvider(): array
    {
        return [
            'order is set' => [
                'headers' => [
                    ['value' => 'sample:header', 'order' => 1],
                ],
                'rules' => [
                    'sample.header' => ['value' => 'sample:header', 'order' => 1],
                ],
                'order' => 20,
                'expectedHeaders' => [
                    ['value' => 'sample:header', 'order' => 1],
                    ['value' => 'parentCategory:titles:default:string', 'order' => 21],
                ],
                'expectedRules' => [
                    'sample.header' => ['value' => 'sample:header', 'order' => 1],
                    'parentCategory.title' => ['value' => 'parentCategory:titles:default:string', 'order' => 21],
                ],
            ],
            'order is not set' => [
                'headers' => [
                    ['value' => 'sample:header', 'order' => 1],
                ],
                'rules' => [
                    'sample.header' => ['value' => 'sample:header', 'order' => 1],
                ],
                'order' => null,
                'expectedHeaders' => [
                    ['value' => 'sample:header', 'order' => 1],
                    ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
                'expectedRules' => [
                    'sample.header' => ['value' => 'sample:header', 'order' => 1],
                    'parentCategory.title' => ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
            ],
            'header already exists' => [
                'headers' => [
                    ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
                'rules' => [
                    'parentCategory.title' => ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
                'order' => null,
                'expectedHeaders' => [
                    ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
                'expectedRules' => [
                    'parentCategory.title' => ['value' => 'parentCategory:titles:default:string', 'order' => 10001],
                ],
            ],
        ];
    }
}
