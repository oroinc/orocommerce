<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Datagrid\Updater;

use Doctrine\Common\Cache\Cache as DoctrineCache;

use OroB2B\Bundle\CheckoutBundle\Datagrid\Updater\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineCache;

    public function setUp()
    {
        $this->doctrineCache = $this->getMockBuilder(DoctrineCache::class)
                                    ->getMock();
    }

    public function testReadThrough()
    {
        $key = 'someKey';

        $callableWasCalled = false;

        $returnData = 1;

        $callable = function () use (&$callableWasCalled, $returnData) {
            $callableWasCalled = true;
            return $returnData;
        };

        $this->doctrineCache->expects($this->once())
                            ->method('contains')
                            ->with($key)
                            ->willReturn(false);

        $this->doctrineCache->expects($this->once())
                            ->method('save')
                            ->with($key, $returnData);

        $testable = new Cache($this->doctrineCache);

        $testable->read($key, $callable);

        $this->assertTrue($callableWasCalled, 'Callable has not been called');
    }

    public function testRead()
    {
        $key = 'someKey';

        $returnData = 1;

        $this->doctrineCache->expects($this->once())
                            ->method('contains')
                            ->with($key)
                            ->willReturn(true);

        $this->doctrineCache->expects($this->once())
                            ->method('fetch')
                            ->with($key)
                            ->willReturn($returnData);

        $testable = new Cache($this->doctrineCache);

        $testable->read(
            $key,
            function () {

            }
        );

        $this->assertEquals(1, $returnData, 'Wrong returning data');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExtremeCases()
    {
        $testable = new Cache($this->doctrineCache);

        $testable->read(
            (object)[],
            10
        );
    }
}
