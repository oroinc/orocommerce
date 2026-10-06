<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Event;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentResponseEventTest extends TestCase
{
    public function testGettersReturnConstructorArguments(): void
    {
        $request = Request::create('/customer/profile');

        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            ['embedded_profile' => '<div>profile</div>']
        );

        self::assertSame('embedded_profile', $event->getFragmentName());
        self::assertSame(['embedded_profile' => '<div>profile</div>'], $event->getContent());
        self::assertSame([EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request], $event->getContext());
        self::assertSame($request, $event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST));
    }

    public function testGetContextValueReturnsNullForMissingValue(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [],
            ['embedded_profile' => '<div>profile</div>']
        );

        self::assertNull($event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST));
    }

    public function testSetContentReplacesContent(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>']
        );

        $event->setContent(['title' => 'Profile']);

        self::assertSame(['title' => 'Profile'], $event->getContent());
    }

    public function testGetContentValueReturnsValue(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>', 'title' => null]
        );

        self::assertSame('<div>profile</div>', $event->getContentValue('embedded_profile'));
        self::assertTrue($event->hasContentValue('embedded_profile'));
        self::assertNull($event->getContentValue('title'));
        self::assertTrue($event->hasContentValue('title'));
    }

    public function testGetContentValueReturnsNullForMissingValue(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>']
        );

        self::assertNull($event->getContentValue('title'));
        self::assertFalse($event->hasContentValue('title'));
    }

    public function testSetContentValueSetsValue(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>', 'title' => '<title>Profile</title>']
        );

        $event->setContentValue('title', 'Profile');
        $event->setContentValue('flashMessages', ['success' => ['Saved.']]);

        self::assertSame(
            [
                'embedded_profile' => '<div>profile</div>',
                'title' => 'Profile',
                'flashMessages' => ['success' => ['Saved.']],
            ],
            $event->getContent()
        );
    }

    public function testRemoveContentValueRemovesValue(): void
    {
        $event = new EmbeddedFragmentResponseEvent(
            'embedded_profile',
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => Request::create('/customer/profile')],
            ['embedded_profile' => '<div>profile</div>', 'title' => 'Profile']
        );

        $event->removeContentValue('title');
        $event->removeContentValue('subtreeUrl');

        self::assertSame(['embedded_profile' => '<div>profile</div>'], $event->getContent());
    }
}
