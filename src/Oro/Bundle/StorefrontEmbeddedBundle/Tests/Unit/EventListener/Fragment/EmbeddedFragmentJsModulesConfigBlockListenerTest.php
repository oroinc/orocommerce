<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentJsModulesConfigBlockListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentJsModulesConfigBlockListenerTest extends TestCase
{
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;

    private EmbeddedFragmentJsModulesConfigBlockListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);

        $this->listener = new EmbeddedFragmentJsModulesConfigBlockListener($this->embeddedRequestChecker);
    }

    /**
     * @dataProvider onFragmentResolveAddsJsModulesConfigBlockOnlyOnFirstRenderProvider
     *
     * @param string[] $expectedBlockIds
     */
    public function testOnFragmentResolveAddsJsModulesConfigBlockOnlyOnFirstRender(
        bool $isReload,
        array $expectedBlockIds
    ): void {
        $request = Request::create('/embedded/fragment/embedded_profile');
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            [],
            [EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile']
        );

        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequestReload')
            ->with(self::identicalTo($request))
            ->willReturn($isReload);

        $this->listener->onFragmentResolve($event);

        self::assertSame($expectedBlockIds, $event->getBlockIds());
    }

    /**
     * @return iterable<string, array{bool, string[]}>
     */
    public static function onFragmentResolveAddsJsModulesConfigBlockOnlyOnFirstRenderProvider(): iterable
    {
        yield 'first render' => [false, ['embedded_profile', 'layout_js_modules_config']];
        yield 'hash navigation reload' => [true, ['embedded_profile']];
    }

    public function testOnFragmentResolveDoesNothingWithoutRequest(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            [],
            [],
            ['embedded_profile']
        );

        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequestReload');

        $this->listener->onFragmentResolve($event);

        self::assertSame(['embedded_profile'], $event->getBlockIds());
    }
}
