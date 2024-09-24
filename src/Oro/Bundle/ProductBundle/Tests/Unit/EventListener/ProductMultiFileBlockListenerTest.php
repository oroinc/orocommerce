<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductMultiFileBlockListener;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class ProductMultiFileBlockListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductMultiFileBlockListener */
    private $productMultiFileBlockListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->productMultiFileBlockListener = new ProductMultiFileBlockListener();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testOnBeforeFormRender(string $pageId, bool $expected): void
    {
        $event = new BeforeFormRenderEvent(
            $this->createMock(FormView::class),
            [],
            $this->createMock(Environment::class),
            null,
            'valid-page-id'
        );

        $this->productMultiFileBlockListener->setPages([$pageId]);
        $this->productMultiFileBlockListener->onBeforeFormRender($event);
        self::assertEquals($event->isPropagationStopped(), $expected);
    }

    public function dataProvider(): array
    {
        return [
            ['pageId' => 'non-valid-page-id', 'expected' => false],
            ['pageId' => 'valid-page-id', 'expected' => true],
        ];
    }
}
