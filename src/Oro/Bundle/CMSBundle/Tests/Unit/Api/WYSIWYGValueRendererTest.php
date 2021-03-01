<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api;

use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Twig\Environment;

class WYSIWYGValueRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var WYSIWYGValueRenderer */
    private $wysiwygValueRenderer;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->wysiwygValueRenderer = new WYSIWYGValueRenderer($this->twig);
    }

    public function testRender(): void
    {
        $this->twig->expects(self::exactly(2))
            ->method('render')
            ->withConsecutive(
                ['OroApiBundle:Field:render_content.html.twig', ['value' => 'value']],
                ['OroApiBundle:Field:render_content.html.twig', ['value' => 'style']]
            )
            ->willReturnOnConsecutiveCalls(
                'rendered value',
                'rendered style'
            );

        self::assertEquals(
            '<style type="text/css">rendered style</style>rendered value',
            $this->wysiwygValueRenderer->render('value', 'style')
        );
    }

    public function testRenderForNullValueAndStyle(): void
    {
        $this->twig->expects(self::never())
            ->method('render');

        self::assertNull($this->wysiwygValueRenderer->render(null, null));
    }

    public function testRenderForNullValue(): void
    {
        $this->twig->expects(self::once())
            ->method('render')
            ->with('OroApiBundle:Field:render_content.html.twig', ['value' => 'style'])
            ->willReturn('rendered style');

        self::assertEquals(
            '<style type="text/css">rendered style</style>',
            $this->wysiwygValueRenderer->render(null, 'style')
        );
    }

    public function testRenderForNullStyle(): void
    {
        $this->twig->expects(self::once())
            ->method('render')
            ->with('OroApiBundle:Field:render_content.html.twig', ['value' => 'value'])
            ->willReturn('rendered value');

        self::assertEquals(
            'rendered value',
            $this->wysiwygValueRenderer->render('value', null)
        );
    }
}
