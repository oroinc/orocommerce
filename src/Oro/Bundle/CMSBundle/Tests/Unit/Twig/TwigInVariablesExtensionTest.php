<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\Twig\TwigInVariablesExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;

class TwigInVariablesExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;
    use LoggerAwareTraitTestTrait;

    /** @var TwigInVariablesExtension */
    private $extension;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $container = self::getContainerBuilder()
            ->add('oro_cms.twig.renderer', $this->twig)
            ->getContainer($this);

        $this->extension = new TwigInVariablesExtension($container);

        $this->setUpLoggerMock($this->extension);
    }

    public function testRenderContent(): void
    {
        $renderedString = 'rendered string';

        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->once())
            ->method('render')
            ->with([])
            ->willReturn($renderedString);
        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->willReturn($template);

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
        $this->twig->expects($this->never())
            ->method('createTemplate');

        $this->assertEmpty(
            self::callTwigFilter($this->extension, 'render_content', [''])
        );
    }

    public function testRenderContentWhenException(): void
    {
        $template = $this->createMock(\Twig\Template::class);
        $template->expects($this->once())
            ->method('render')
            ->with([])
            ->willThrowException(new \Exception('sample error'));
        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->willReturn($template);

        $this->assertLoggerErrorMethodCalled();

        $this->assertEmpty(
            self::callTwigFilter($this->extension, 'render_content', ['{{placeholder}}'])
        );
    }
}
