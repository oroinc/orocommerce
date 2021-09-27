<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Storage\Stub\StubAbstractSessionDataStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractSessionDataStorageTest extends \PHPUnit\Framework\TestCase
{
    protected RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    protected SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    protected SessionBagInterface $sessionBag;

    protected AbstractSessionDataStorage $storage;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->session = $this->createMock(SessionInterface::class);
        $this->sessionBag = new AttributeBag();

        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack
            ->push($request);
        $this->session
            ->expects(self::atMost(1))
            ->method('getBag')
            ->willReturn($this->sessionBag);

        $this->initStorage();
    }

    protected function initStorage(): void
    {
        $this->storage = new StubAbstractSessionDataStorage($this->session);
    }

    abstract protected function getKey(): string;

    public function testSet(): void
    {
        $data = [['productId' => 42, 'qty' => 100]];

        $this->storage->set($data);

        self::assertEquals([$this->getKey() => serialize($data)], $this->sessionBag->all());
    }

    /**
     * @dataProvider getProductsDataProvider
     *
     * @param mixed $storageData
     * @param array $expectedData
     */
    public function testGet(mixed $storageData, array $expectedData): void
    {
        $this->sessionBag->set($this->getKey(), $storageData);

        self::assertEquals($expectedData, $this->storage->get());
    }

    public function getProductsDataProvider(): array
    {
        return [
            [null, []],
            ['test', []],
            [10, []],
            ['a:1:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}}', [['productId' => 42, 'qty' => 100]]],
            [
                'a:2:{i:0;a:2:{s:9:"productId";i:42;s:3:"qty";i:100;}i:1;a:2:{s:9:"productId";i:43;s:3:"qty";i:101;}}',
                [['productId' => 42, 'qty' => 100], ['productId' => 43, 'qty' => 101]],
            ],
            ['[{invalid_serialized:100}]', []],
            [false, []],
            ['', []],
            ['[]', []],
        ];
    }

    public function testRemove(): void
    {
        $this->sessionBag->set($this->getKey(), ['sample-key' => 'sample-value']);

        $this->storage->remove();

        self::assertEmpty($this->sessionBag->all());
    }

    public function testNothingToRemove(): void
    {
        $this->storage->remove();

        self::assertEmpty($this->sessionBag->all());
    }

    public function testNothingToGet(): void
    {
        self::assertEmpty($this->storage->get());
    }
}
