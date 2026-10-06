<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment\EmbeddedFragmentSubtreeUrlResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentSubtreeUrlResponseListenerTest extends TestCase
{
    public function testOnFragmentResponseSetsPathInfoWithoutQueryAsSubtreeUrl(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_shopping_list_view',
            [
                EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create(
                    '/customer/shoppinglist/42?grid%5Ba%5D=b'
                ),
            ],
            ['embedded_shopping_list_view' => '<div>list</div>']
        );

        (new EmbeddedFragmentSubtreeUrlResponseListener())->onFragmentResponse($event);

        self::assertSame(
            ['embedded_shopping_list_view' => '<div>list</div>', 'subtreeUrl' => '/customer/shoppinglist/42'],
            $event->getContent()
        );
    }

    public function testOnFragmentResponseDoesNothingWithoutRequest(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_shopping_list_view',
            [],
            ['embedded_shopping_list_view' => '<div>list</div>']
        );

        (new EmbeddedFragmentSubtreeUrlResponseListener())->onFragmentResponse($event);

        self::assertSame(['embedded_shopping_list_view' => '<div>list</div>'], $event->getContent());
    }
}
