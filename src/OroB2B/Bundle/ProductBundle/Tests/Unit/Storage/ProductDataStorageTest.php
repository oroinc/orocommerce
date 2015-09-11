<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Storage;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected $session;

    /**
     * @var ProductDataStorage
     */
    protected $storage;

    protected function setUp()
    {
        $this->session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $this->storage = new ProductDataStorage($this->session);
    }

    protected function tearDown()
    {
        unset($this->storage, $this->session);
    }

    public function testSet()
    {
        $data = [['productId' => 42, 'qty' => 100]];

        $this->session->expects($this->once())
            ->method('set')
            ->with(ProductDataStorage::PRODUCT_DATA_KEY, serialize($data));

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
        $this->session->expects($this->once())
            ->method('get')
            ->with(ProductDataStorage::PRODUCT_DATA_KEY)
            ->willReturn($storageData);

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
        ];
    }

    public function testRemove()
    {
        $this->session->expects($this->once())
            ->method('remove')
            ->with(ProductDataStorage::PRODUCT_DATA_KEY);

        $this->storage->remove();
    }
}
