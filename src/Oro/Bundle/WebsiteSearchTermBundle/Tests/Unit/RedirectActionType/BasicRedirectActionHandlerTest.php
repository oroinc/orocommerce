<?php

declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\RedirectActionType;

use Oro\Bundle\RedirectBundle\Factory\SubRequestFactory;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BasicRedirectActionHandlerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private HttpKernelInterface|MockObject $httpKernel;

    private SubRequestFactory|MockObject $subRequestFactory;

    private BasicRedirectActionHandler $handler;

    protected function setUp(): void
    {
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->subRequestFactory = $this->createMock(SubRequestFactory::class);

        $this->handler = new BasicRedirectActionHandler($this->httpKernel, $this->subRequestFactory);

        $this->setUpLoggerMock($this->handler);
    }

    public function testGetResponseWhenNotAbsoluteUrlAndNotRedirect301(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('system_page')
            ->setRedirect301(false);

        $redirectUrl = '/sample/url';

        $request = new Request();
        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with($request, $redirectUrl)
            ->willReturn($subRequest);

        $response = new Response('page content');
        $this->httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest, HttpKernelInterface::MAIN_REQUEST)
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Forwarding to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );

        self::assertSame($response, $this->handler->getResponse($request, $searchTerm, $redirectUrl));
    }

    public function testGetResponseWhenNotAbsoluteUrlAndNotRedirect301AndNotSuccessful(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('system_page')
            ->setRedirect301(false);

        $redirectUrl = '/sample/url';

        $request = new Request();
        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with($request, $redirectUrl)
            ->willReturn($subRequest);

        $response = new Response('Bad Request', 400);
        $this->httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest, HttpKernelInterface::MAIN_REQUEST)
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to forward to "{url}" from the search page "{search}" for the search term #{search_term_id}:'
                . ' response status code is {response_status_code}.',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                    'response_status_code' => $response->getStatusCode(),
                ]
            );

        self::assertNull($this->handler->getResponse($request, $searchTerm, $redirectUrl));
    }

    public function testGetResponseWhenAbsoluteUrl(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $redirectUrl = 'https://example.com';
        $request = new Request();
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $response = new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );

        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals($response->getTargetUrl(), $actualResponse->getTargetUrl());
    }

    public function testGetResponseWhenAbsoluteUrlWithQueryParameters(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $redirectUrl = 'https://example.com?sample_key=sample_value';
        $request = new Request();
        $request->query->set('search', 'sample_phrase');
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $response = new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl . '&search=sample_phrase',
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );

        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals($response->getTargetUrl() . '&search=sample_phrase', $actualResponse->getTargetUrl());
    }

    public function testGetResponseWhenAbsoluteUrlWithSameHost(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $redirectUrl = 'https://example.com?sample_key=sample_value';
        $request = new Request();
        $request->headers->set('host', 'example.com');
        $request->query->set('search', 'sample_phrase');
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl . '&search=sample_phrase&'
                        . BasicRedirectActionHandler::SKIP_SEARCH_TERM . '=1',
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );

        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals(
            'https://example.com?sample_key=sample_value&search=sample_phrase&skip_search_term=1',
            $actualResponse->getTargetUrl()
        );
    }

    public function testGetResponseWhenAbsoluteUrlWithExistingSearchParameter(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $redirectUrl = 'https://example.com?search=sample_phrase';
        $request = new Request();
        $request->headers->set('host', 'example.com');
        $request->query->set('search', 'original_phrase');
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl . '&' . BasicRedirectActionHandler::SKIP_SEARCH_TERM . '=1',
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );

        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals(
            'https://example.com?search=sample_phrase&skip_search_term=1',
            $actualResponse->getTargetUrl()
        );
    }

    public function testGetResponseWhenRedirect301(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirect301(true);

        $redirectUrl = '/sample/url';
        $request = new Request();
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $response = new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );


        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals($response->getTargetUrl(), $actualResponse->getTargetUrl());
    }

    public function testGetResponseWhenRedirect301WithQueryParameters(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirect301(true);

        $redirectUrl = '/sample/url';
        $request = new Request();
        $request->query->set('search', 'sample_phrase');
        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $response = new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => $redirectUrl . '?sample_key=sample_value',
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );


        $actualResponse = $this->handler->getResponse(
            $request,
            $searchTerm,
            $redirectUrl,
            ['sample_key' => 'sample_value']
        );
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals($response->getTargetUrl() . '?sample_key=sample_value', $actualResponse->getTargetUrl());
    }

    public function testGetResponseWhenRedirect301WithBaseUrl(): void
    {
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirect301(true);

        $redirectUrl = '/sample/url';
        $request = new Request();
        ReflectionUtil::setPropertyValue($request, 'baseUrl', '/index_dev.php');

        $this->subRequestFactory
            ->expects(self::never())
            ->method('createSubRequest');

        $this->httpKernel
            ->expects(self::never())
            ->method('handle');

        $response = new RedirectResponse($request->getBaseUrl() . $redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                [
                    'url' => '/index_dev.php' . $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'search_term_id' => $searchTerm->getId(),
                    'request' => $request,
                ]
            );


        $actualResponse = $this->handler->getResponse($request, $searchTerm, $redirectUrl);
        self::assertInstanceOf(RedirectResponse::class, $actualResponse);
        self::assertEquals($response->getTargetUrl(), $actualResponse->getTargetUrl());
    }
}
