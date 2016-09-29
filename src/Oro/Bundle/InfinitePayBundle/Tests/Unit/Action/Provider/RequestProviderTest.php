<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\RequestProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class RequestProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1'], [], json_encode([
            'foo' => 'bar',
        ]));

        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);
    }

    public function testGetClientIp()
    {
        $requestProvider = new RequestProvider($this->requestStack);
        $this->assertEquals('127.0.0.1', $requestProvider->getClientIp());
    }
}
