<?php

namespace Oro\Bundle\RedirectBundle\Test\Unit\Routing;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlProviderInterface;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Sluggable Url Generator Tests
 * - invalid routes test
 */
class SluggableUrlGeneratorTest extends TestCase
{
    private MockObject|SluggableUrlGenerator $urlGenerator;
    #[\Override]
    protected function setUp(): void
    {
        $this->urlGenerator = new SluggableUrlGenerator(
            $this->createMock(SluggableUrlProviderInterface::class),
            $this->createMock(ContextUrlProviderRegistry::class),
            $this->createMock(LocalizationProviderInterface::class),
            $this->createMock(ConfigManager::class)
        );
    }

    /**
     * @dataProvider invalidRouteNameDataProvider
     */
    public function testInvalidRouteName(string $name)
    {
        self::expectException(RouteNotFoundException::class);
        self::expectExceptionMessage('Unable to generate a URL for the named route'.
            ' as such route does not fit [a-zA-Z0-9_] regexp.');

        $this->urlGenerator->generate($name);
    }

    public function invalidRouteNameDataProvider(): array
    {
        return [
            ['))) OR 1 = 1'],
            ['/uri'],
            ['invalid-route'],
            ['https://example.com/']
        ];
    }
}
