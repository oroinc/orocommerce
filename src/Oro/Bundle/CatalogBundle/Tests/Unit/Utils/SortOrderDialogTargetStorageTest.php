<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Utils;

use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SortOrderDialogTargetStorageTest extends TestCase
{
    private RequestStack $requestStack;

    private SortOrderDialogTargetStorage $storage;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        $this->storage = new SortOrderDialogTargetStorage($this->requestStack);
    }

    public function testAddTargetWhenNoRequest(): void
    {
        self::assertFalse($this->storage->addTarget('sample_name', 42));
    }

    public function testAddTargetWhenNoSession(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        self::assertFalse($this->storage->addTarget('sample_name', 42));
    }

    public function testAddTargetWhenHasSession(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn([]);

        $name = 'sample_name';
        $id = 42;
        $session
            ->expects(self::once())
            ->method('set')
            ->with('sortOrderDialogTargets', [$name => [$id => $id]]);

        self::assertTrue($this->storage->addTarget($name, $id));
    }

    public function testAddTargetWhenHasSessionAndAlreadyExists(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $name = 'sample_name';
        $id = 42;

        $targets = [$name => [$id => $id], 'another_name' => [43 => 43]];
        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn($targets);

        $session
            ->expects(self::once())
            ->method('set')
            ->with('sortOrderDialogTargets', $targets);

        self::assertTrue($this->storage->addTarget($name, $id));
    }

    public function testHasTargetWhenNoRequest(): void
    {
        self::assertFalse($this->storage->hasTarget('sample_name', 42));
    }

    public function testHasTargetWhenNoSession(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        self::assertFalse($this->storage->hasTarget('sample_name', 42));
    }

    public function testHasTargetWhenHasSessionAndNoTarget(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn([]);

        self::assertFalse($this->storage->hasTarget('sample_name', 42));
    }

    public function testHasTargetWhenHasSessionAndHasTarget(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $name = 'sample_name';
        $id = 42;

        $targets = [$name => [$id => $id], 'another_name' => [43 => 43]];
        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn($targets);

        self::assertTrue($this->storage->hasTarget($name, $id));
    }

    public function testRemoveTargetWhenNoRequest(): void
    {
        self::assertFalse($this->storage->removeTarget('sample_name', 42));
    }

    public function testRemoveTargetWhenNoSession(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        self::assertFalse($this->storage->removeTarget('sample_name', 42));
    }

    public function testRemoveTargetWhenHasSessionAndNoTarget(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn([]);

        $session
            ->expects(self::never())
            ->method('set')
            ->with('sortOrderDialogTargets', []);

        self::assertFalse($this->storage->removeTarget('sample_name', 42));
    }

    public function testRemoveTargetWhenHasSessionAndHasTarget(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        $this->requestStack->push($request);

        $name = 'sample_name';
        $id = 42;

        $targets = [$name => [$id => $id], 'another_name' => [43 => 43]];
        $session
            ->expects(self::once())
            ->method('get')
            ->with('sortOrderDialogTargets', [])
            ->willReturn($targets);

        $session
            ->expects(self::once())
            ->method('set')
            ->with('sortOrderDialogTargets', [$name => [], 'another_name' => [43 => 43]]);

        self::assertTrue($this->storage->removeTarget($name, $id));
    }
}
