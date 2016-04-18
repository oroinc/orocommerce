<?php

namespace OroB2B\Bundle\FrontendBundle\Test;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\BrowserKit\Request as InternalRequest;
use Symfony\Component\BrowserKit\Response as InternalResponse;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\TestFrameworkBundle\Test\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * @return string
     */
    public static function getHashNavigationHeader()
    {
        return 'HTTP_' . strtoupper(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }

    /**
     * @return array
     */
    public static function generateNoHashNavigationHeader()
    {
        return [self::getHashNavigationHeader() => 0];
    }

    /**
     * {@inheritdoc}
     */
    public function request(
        $method,
        $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        $content = null,
        $changeHistory = true
    ) {
        $hashNavigationHeader = self::getHashNavigationHeader();
        if ($this->isHashNavigationRequest($uri, $parameters, $server)) {
            $server[$hashNavigationHeader] = 1;
        }

        parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        if ($this->isHashNavigationResponse($this->response, $server)) {
            /** @var InternalRequest $internalRequest */
            $internalRequest = $this->internalRequest;
            /** @var Response $response */
            $response = $this->response;

            $content = json_decode($response->getContent(), true);

            if ($this->isRedirectResponse($content)) {
                $this->redirect = $content['location'];
                // force regular redirect
                if (!empty($content['fullRedirect'])) {
                    $this->internalRequest = new InternalRequest(
                        $internalRequest->getUri(),
                        $internalRequest->getMethod(),
                        $internalRequest->getParameters(),
                        $internalRequest->getFiles(),
                        $internalRequest->getCookies(),
                        array_merge($internalRequest->getServer(), [$hashNavigationHeader => 0]),
                        $internalRequest->getContent()
                    );
                }
                $response->setContent('');
                $response->setStatusCode(302);
                /** @var InternalResponse $internalResponse */
                $internalResponse = $this->internalResponse;
                $this->internalResponse = new InternalResponse('', 302, $internalResponse->getHeaders());
                if ($this->followRedirects && $this->redirect) {
                    return $this->crawler = $this->followRedirect();
                }
            }

            if ($this->isContentResponse($content)) {
                $response->setContent($this->buildHtml($content));
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                $this->crawler = $this->createCrawlerFromContent(
                    $internalRequest->getUri(),
                    $response->getContent(),
                    'text/html'
                );
            }
        }

        $this->checkForBackendUrls($uri, $this->crawler);

        return $this->crawler;
    }

    /**
     * @param null|array $content
     * @return bool
     */
    protected function isRedirectResponse($content)
    {
        return $content && !empty($content['redirect']);
    }

    /**
     * @param null|array $content
     * @return bool
     */
    protected function isContentResponse($content)
    {
        return $content && array_key_exists('content', $content);
    }

    /**
     * @param $uri
     * @param array $parameters
     * @param array $server
     * @return bool
     */
    protected function isHashNavigationRequest($uri, array $parameters, array $server)
    {
        $isWidget = !empty($parameters['_widgetContainer']) || strpos($uri, '_widgetContainer=') !== false;

        return !$isWidget &&
            !$this->isFrontendUri($uri) &&
            !array_key_exists(self::getHashNavigationHeader(), $server);
    }

    /**
     * @param object|Response $response
     * @param array $server
     * @return bool
     */
    protected function isHashNavigationResponse($response, array $server)
    {
        if (empty($server[self::getHashNavigationHeader()])) {
            return false;
        }

        return $response instanceof Response &&
            $response->getStatusCode() === 200 &&
            $response->headers->get('Content-Type') === 'application/json';
    }

    /**
     * @param array $content
     * @return string
     */
    protected function buildHtml(array $content)
    {
        $title = !empty($content['title']) ? $content['title'] : '';

        $flashMessages = '';
        if (!empty($content['flashMessages'])) {
            foreach ($content['flashMessages'] as $type => $messages) {
                foreach ($messages as $message) {
                    $flashMessages .= sprintf('<div class="%s">%s</div>', $type, $message);
                }
            }
        }

        $html =
            '<html>
                <head><title>%s</title></head>
                <body>%s%s</body>
            </html>';

        return sprintf($html, $title, $flashMessages, $content['content']);
    }

    /**
     * @param $uri string
     * @param $crawler
     */
    protected function checkForBackendUrls($uri, Crawler $crawler)
    {
        if ($this->isFrontendUri($uri)) {
            $backendPrefix = $this->getBackendPrefix();
            if (count($crawler) && strpos($crawler->html(), $backendPrefix) !== false) {
                throw new \PHPUnit_Framework_AssertionFailedError(
                    sprintf('Page "%s" contains backend prefix "%s".', $uri, $backendPrefix)
                );
            }
        }
    }

    /**
     * @param string $uri
     * @return bool
     */
    protected function isFrontendUri($uri)
    {
        return strpos($uri, $this->getBackendPrefix()) === false;
    }

    /**
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('web_backend_prefix');
    }
}
