<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use OroB2B\Bundle\ProductBundle\Model\AbstractDataStorageProcessor;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Model\Stub\DataStorageProcessorStub;

class AbstractDataStorageProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage
     */
    protected $storage;

    /**
     * @var AbstractDataStorageProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->router, $this->storage);
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param string $redirectRouteName
     * @param string $redirectUrl
     * @param null|Response $expectedResponse
     */
    public function testProcess($redirectRouteName, $redirectUrl, Response $expectedResponse = null)
    {
        $data = ['data' => ['param' => 42]];

        if ($redirectRouteName) {
            $this->router->expects($this->once())
                ->method('generate')
                ->with($redirectRouteName, ['quick_add' => 1], UrlGeneratorInterface::ABSOLUTE_PATH)
                ->willReturn($redirectUrl);
        } else {
            $this->router->expects($this->never())
                ->method($this->anything());
        }

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $processor = new DataStorageProcessorStub($this->router, $this->storage, $redirectRouteName);

        $this->assertEquals($expectedResponse, $processor->process($data, new Request()));
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        $response = new RedirectResponse('/redirect/url');

        return [
            [
                'redirectRouteName' => null,
                'redirectUrl' => null,
                'expectedResponse' => null,
            ],
            [
                'redirectRouteName' => 'redirect_route',
                'redirectUrl' => $response->getTargetUrl(),
                'expectedResponse' => $response,
            ]
        ];
    }
}
