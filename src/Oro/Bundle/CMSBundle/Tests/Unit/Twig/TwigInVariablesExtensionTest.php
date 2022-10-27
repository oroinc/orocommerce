<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\Twig\TwigInVariablesExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\TemplateWrapper;

class TwigInVariablesExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $cmsTwigRenderer;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var TwigInVariablesExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->cmsTwigRenderer = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_cms.twig.renderer', $this->cmsTwigRenderer)
            ->getContainer($this);

        $this->extension = new TwigInVariablesExtension($container, $this->logger);
    }

    public function testRenderContent(): void
    {
        $renderedString = 'rendered string';

        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->once())
            ->method('render')
            ->with([])
            ->willReturn($renderedString);
        $this->cmsTwigRenderer->expects($this->once())
            ->method('createTemplate')
            ->willReturn(new TemplateWrapper($this->cmsTwigRenderer, $template));

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $renderedString,
            self::callTwigFilter($this->extension, 'render_content', ['{{placeholder}}'])
        );
    }

    public function testRenderContentWhenEmpty(): void
    {
        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->never())
            ->method('render');
        $this->cmsTwigRenderer->expects($this->never())
            ->method('createTemplate');

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertEmpty(
            self::callTwigFilter($this->extension, 'render_content', [''])
        );
    }

    public function testRenderContentWhenException(): void
    {
        $exception = new \Exception('sample error');

        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->once())
            ->method('render')
            ->with([])
            ->willThrowException($exception);
        $this->cmsTwigRenderer->expects($this->once())
            ->method('createTemplate')
            ->willReturn(new TemplateWrapper($this->cmsTwigRenderer, $template));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not render content: {{placeholder}}', ['exception' => $exception]);

        $this->assertEmpty(
            self::callTwigFilter($this->extension, 'render_content', ['{{placeholder}}'])
        );
    }
}
