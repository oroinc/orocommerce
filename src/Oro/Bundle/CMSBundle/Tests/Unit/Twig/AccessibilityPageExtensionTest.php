<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\Provider\AccessibilityPageProvider;
use Oro\Bundle\CMSBundle\Twig\AccessibilityPageExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccessibilityPageExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private AccessibilityPageProvider&MockObject $accessibilityPageProvider;
    private AccessibilityPageExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessibilityPageProvider = $this->createMock(AccessibilityPageProvider::class);
        $this->extension = new AccessibilityPageExtension($this->accessibilityPageProvider);
    }

    public function testGetHelpPageUrlReturnsUrl(): void
    {
        $this->accessibilityPageProvider->expects(self::once())
            ->method('getAccessibilityPageUrl')
            ->willReturn('/accessibility');

        self::assertSame(
            '/accessibility',
            self::callTwigFunction($this->extension, 'oro_accessibility_page_url', [])
        );
    }

    public function testGetHelpPageUrlReturnsNullWhenNotConfigured(): void
    {
        $this->accessibilityPageProvider->expects(self::once())
            ->method('getAccessibilityPageUrl')
            ->willReturn(null);

        self::assertNull(
            self::callTwigFunction($this->extension, 'oro_accessibility_page_url', [])
        );
    }

    public function testGetHelpPageTitleReturnsTitle(): void
    {
        $this->accessibilityPageProvider->expects(self::once())
            ->method('getAccessibilityPageTitle')
            ->willReturn('Accessibility');

        self::assertSame(
            'Accessibility',
            self::callTwigFunction($this->extension, 'oro_accessibility_page_title', [])
        );
    }

    public function testGetHelpPageTitleReturnsEmptyStringWhenNotConfigured(): void
    {
        $this->accessibilityPageProvider->expects(self::once())
            ->method('getAccessibilityPageTitle')
            ->willReturn('');

        self::assertSame(
            '',
            self::callTwigFunction($this->extension, 'oro_accessibility_page_title', [])
        );
    }
}
