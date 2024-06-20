<?php

declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType;

use Oro\Bundle\RedirectBundle\Factory\SubRequestFactory;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\Routing\UrlUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Basic handler for the search term redirect action type.
 */
class BasicRedirectActionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const SKIP_SEARCH_TERM = 'skip_search_term';

    public function __construct(
        private HttpKernelInterface $httpKernel,
        private SubRequestFactory $subRequestFactory
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     * @param SearchTerm $searchTerm
     * @param string $redirectUrl
     * @param array|null $getParameters Only for forward, ignored for 301 redirect.
     * @param array|null $postParameters Only for forward, ignored for 301 redirect.
     * @param array|null $requestAttributes Only for forward, ignored for 301 redirect.
     *
     * @return Response|null
     * @throws \Exception
     */
    public function getResponse(
        Request $request,
        SearchTerm $searchTerm,
        string $redirectUrl,
        ?array $getParameters = null,
        ?array $postParameters = null,
        ?array $requestAttributes = null
    ): ?Response {
        $loggingContext = [
            'url' => $redirectUrl,
            'search' => $request->get('search', 'n/a'),
            'search_term_id' => $searchTerm->getId(),
            'request' => $request,
        ];

        $isAbsoluteUrl = UrlUtil::isAbsoluteUrl($redirectUrl);
        if (!$isAbsoluteUrl) {
            $redirectUrl = UrlUtil::getAbsolutePath($redirectUrl, $request->getBaseUrl());
        }

        if ($isAbsoluteUrl || $searchTerm->isRedirect301()) {
            if ($getParameters === null) {
                $getParameters = $request->query->all();
            }

            if ($request->getHttpHost() === UrlUtil::getHttpHost($redirectUrl)) {
                // Adds the skip flag to avoid possible infinite redirects.
                $getParameters += [self::SKIP_SEARCH_TERM => '1'];
            }

            if ($getParameters) {
                $redirectUrl = UrlUtil::addQueryParameters($redirectUrl, $getParameters);
            }

            $response = new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY);

            $this->logger->debug(
                'Redirecting to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
                ['url' => $redirectUrl] + $loggingContext
            );

            return $response;
        }

        $this->logger->debug(
            'Forwarding to "{url}" from the search page "{search}" for the search term #{search_term_id}.',
            $loggingContext
        );

        $subRequest = $this->subRequestFactory
            ->createSubRequest($request, $redirectUrl, $getParameters, $postParameters, $requestAttributes);
        $response = $this->httpKernel->handle($subRequest);

        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->logger->warning(
                'Failed to forward to "{url}" from the search page "{search}" for the search term #{search_term_id}:'
                . ' response status code is {response_status_code}.',
                $loggingContext + ['response_status_code' => $response->getStatusCode()]
            );

            // Response is not successful.
            return null;
        }

        return $response;
    }
}
