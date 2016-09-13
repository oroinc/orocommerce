<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait FrontendRequestTrait
{
    /**
     * Substitutes request stack in container to treat current request as
     * frontend request for testing purposes.
     */
    protected function substituteRequestStack()
    {
        /** @var WebTestCase $this */
        $requestStackMock = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock
            ->expects($this->any())
            ->method('getPathInfo')
            ->willReturn('');

        $requestStackMock
            ->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->getContainer()->set('request_stack', $requestStackMock);
    }
}
