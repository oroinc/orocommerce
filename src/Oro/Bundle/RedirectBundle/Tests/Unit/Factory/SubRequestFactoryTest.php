<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Factory;

use Oro\Bundle\RedirectBundle\Factory\SubRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class SubRequestFactoryTest extends TestCase
{
    private RouterInterface|MockObject $router;

    private SubRequestFactory $factory;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->factory = new SubRequestFactory($this->router);
    }

    public function testCreateSubRequest(): void
    {
        $requestContext = new RequestContext();
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($requestContext);

        $url = '/sample/page';
        $routeInfo = [
            '_route' => 'sample_route_name',
            '_controller' => 'SampleController',
            'sample_key' => 'sample_value',
        ];
        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($url)
            ->willReturn($routeInfo);

        $request = new Request();
        $subRequest = $request->duplicate(null, null, $routeInfo);
        $subRequest->attributes->add(['_route_params' => ['sample_key' => 'sample_value']]);

        self::assertEquals($subRequest, $this->factory->createSubRequest($request, $url));
    }

    public function testCreateSubRequestWhenBaseUrl(): void
    {
        $requestContext = new RequestContext('/base/url');
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($requestContext);

        $url = '/sample/page';
        $routeInfo = [
            '_route' => 'sample_route_name',
            '_controller' => 'SampleController',
            'sample_key' => 'sample_value',
        ];
        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($url)
            ->willReturn($routeInfo);

        $request = new Request();
        $subRequest = $request->duplicate(null, null, $routeInfo);
        $subRequest->attributes->add(['_route_params' => ['sample_key' => 'sample_value']]);

        self::assertEquals(
            $subRequest,
            $this->factory->createSubRequest($request, $requestContext->getBaseUrl() . $url)
        );
    }

    public function testCreateSubRequestWithExtraParameters(): void
    {
        $getParameters = ['sample_get' => 'sample_get_value'];
        $postParameters = ['sample_post' => 'sample_post_value'];
        $requestAttributes = ['sample_attr' => 'sample_attr_value'];
        $requestContext = new RequestContext();
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($requestContext);

        $url = '/sample/page';
        $routeInfo = [
            '_route' => 'sample_route_name',
            '_controller' => 'SampleController',
            'sample_key' => 'sample_value',
        ];
        $this->router
            ->expects(self::once())
            ->method('match')
            ->with($url)
            ->willReturn($routeInfo);

        $request = new Request();
        $subRequest = $request->duplicate($getParameters, $postParameters, $routeInfo + $requestAttributes);
        $subRequest->attributes->add(['_route_params' => ['sample_key' => 'sample_value']] + $requestAttributes);

        self::assertEquals(
            $subRequest,
            $this->factory->createSubRequest(
                $request,
                $url,
                $getParameters,
                $postParameters,
                $requestAttributes
            )
        );
    }
}
