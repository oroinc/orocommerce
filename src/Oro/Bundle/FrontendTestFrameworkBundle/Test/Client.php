<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\TestFrameworkBundle\Test\Client as BaseClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle backend prefix for application with front store
 */
class Client extends BaseClient
{
    private const BACKOFFICE_THEME_PATH = 'build/admin';

    /**
     * {@inheritdoc}
     */
    public function request(
        string $method,
        string $uri,
        array  $parameters = [],
        array  $files = [],
        array  $server = [],
        string $content = null,
        bool   $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        $this->checkForBackendUrls($uri, $crawler);

        return $crawler;
    }

    /**
     * @param array|string $gridParameters
     * @param array $filter
     * @param bool $isRealRequest
     * @return Response
     */
    public function requestFrontendGrid($gridParameters, $filter = [], $isRealRequest = false)
    {
        return $this->requestGrid($gridParameters, $filter, $isRealRequest, 'oro_frontend_datagrid_index');
    }

    /**
     * {@inheritdoc}
     */
    protected function isHashNavigationRequest($uri, array $parameters, array $server)
    {
        // no hash navigation at frontend
        return parent::isHashNavigationRequest($uri, $parameters, $server) && !$this->isFrontendUri($uri);
    }

    /**
     * Response from frontend must not contain backend url prefix
     */
    protected function checkForBackendUrls($uri, Crawler $crawler)
    {
        if ($this->isFrontendUri($uri)) {
            $backendPrefix = $this->getBackendPrefix();
            if (!count($crawler)) {
                return;
            }
            $html = $crawler->html();

            $backofficeThemePathOccurrences = substr_count($html, self::BACKOFFICE_THEME_PATH);

            if (substr_count($html, $backendPrefix) > $backofficeThemePathOccurrences) {
                throw new \PHPUnit\Framework\AssertionFailedError(
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
