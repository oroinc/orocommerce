<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api;

use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Component\Testing\Unit\TestContainerBuilder;
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

        $container = TestContainerBuilder::create()
            ->add(Environment::class, $this->twig)
            ->getContainer($this);

        $this->wysiwygValueRenderer = new WYSIWYGValueRenderer($container);
    }

    public function testRender(): void
    {
        $expected = 'rendered value with style';
        $value = 'sample value';
        $style = 'sample style';
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroCMS/Api/Field/render_content.html.twig', ['value' => $value, 'style' => $style])
            ->willReturn($expected);

        self::assertEquals($expected, $this->wysiwygValueRenderer->render($value, $style));
    }

    public function testRenderForNullValueAndStyle(): void
    {
        $this->twig->expects(self::never())
            ->method('render');

        self::assertNull($this->wysiwygValueRenderer->render(null, null));
    }

    public function testRenderForNullValue(): void
    {
        $expected = 'rendered style';
        $style = 'sample style';
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroCMS/Api/Field/render_content.html.twig', ['value' => '', 'style' => $style])
            ->willReturn($expected);

        self::assertEquals($expected, $this->wysiwygValueRenderer->render(null, $style));
    }

    public function testRenderForNullStyle(): void
    {
        $expected = 'rendered value';
        $value = 'sample value';
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroCMS/Api/Field/render_content.html.twig', ['value' => $value, 'style' => ''])
            ->willReturn($expected);

        self::assertEquals($expected, $this->wysiwygValueRenderer->render($value, null));
    }
}
