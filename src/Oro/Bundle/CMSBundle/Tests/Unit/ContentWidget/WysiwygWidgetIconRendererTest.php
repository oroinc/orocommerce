<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\WysiwygWidgetIconRenderer;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class WysiwygWidgetIconRendererTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private MockObject|Environment $twig;
    private WysiwygWidgetIconRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->renderer = new WysiwygWidgetIconRenderer($this->twig);
        $this->setUpLoggerMock($this->renderer);
    }

    public function testRender(): void
    {
        $widgetName = 'test-icon';
        $widgetOption = 'test-option';
        $expected = 'rendered icon';

        $this->assertLoggerNotCalled();
        $this->twig->expects(self::once())->method('render')->with(
            self::isType('string'),
            [
                'options' => [
                    'name' => $widgetName,
                    'id' => $widgetOption,
                ],
            ]
        )->willReturn($expected);
        self::assertEquals(
            $expected,
            $this->renderer->render($widgetName, ['name' => 'another-icon', 'id' => $widgetOption])
        );
    }

    public function testRenderThrowable(): void
    {
        $widgetName = 'test-icon';
        $widgetOption = 'test-option';
        $expected = '';
        $exception = new \Exception('test');

        $this->assertLoggerErrorMethodCalled();
        $this->twig->expects(self::once())->method('render')->with(
            self::isType('string'),
            [
                'options' => [
                    'name' => $widgetName,
                    'id' => $widgetOption,
                ],
            ]
        )->willThrowException($exception);
        self::assertEquals(
            $expected,
            $this->renderer->render($widgetName, ['name' => 'another-icon', 'id' => $widgetOption])
        );
    }
}
