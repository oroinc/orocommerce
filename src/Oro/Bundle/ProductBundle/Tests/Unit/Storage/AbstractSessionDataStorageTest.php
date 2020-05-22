<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Storage\Stub\StubAbstractSessionDataStorage;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AbstractSessionDataStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    protected $session;

    /**
     * @var AbstractSessionDataStorage
     */
    protected $storage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AttributeBagInterface
     */
    protected $sessionBag;

    protected function setUp(): void
    {
        $this->session = $this->createMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $this->sessionBag = $this
            ->createMock('Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface');
        $this->session->expects($this->atMost(1))->method('getBag')->willReturn($this->sessionBag);

        $this->initStorage();
    }

    protected function initStorage()
    {
        $this->storage = new StubAbstractSessionDataStorage($this->session);
    }

    protected function tearDown(): void
    {
        unset($this->storage, $this->session);
    }

    public function testSet()
    {
        $data = [['productId' => 42, 'qty' => 100]];

        $this->sessionBag->expects($this->once())
            ->method('set')
            ->with($this->isType('string'), serialize($data));

        $this->storage->set($data);
    }

    /**
     * @dataProvider getProductsDataProvider
     *
     * @param mixed $storageData
     * @param array $expectedData
     */
    public function testGet($storageData, array $expectedData)
    {
        $this->sessionBag->expects($this->once())
            ->method('get')
            ->with($this->isType('string'))
            ->willReturn($storageData);

        $this->sessionBag->expects($this->once())->method('has')->willReturn(true);

        $this->assertEquals($expectedData, $this->storage->get());
    }

    /**
     * @return array
     */
    public function getProductsDataProvider()
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

    public function testRemove()
    {
        $this->sessionBag->expects($this->once())->method('has')->willReturn(true);

        $this->sessionBag->expects($this->once())
            ->method('remove')
            ->with($this->isType('string'));

        $this->storage->remove();
    }

    public function testNothingToRemove()
    {
        $this->sessionBag->expects($this->once())->method('has')->willReturn(false);
        $this->sessionBag->expects($this->never())->method('remove');

        $this->storage->remove();
    }

    public function testNothingToGet()
    {
        $this->sessionBag->expects($this->once())->method('has')->willReturn(false);
        $this->sessionBag->expects($this->never())->method('get');

        $this->storage->get();
    }
}
