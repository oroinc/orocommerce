<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\TestFrameworkBundle\Test\Client as BaseClient;
use Symfony\Component\DomCrawler\Crawler;

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
    ): Crawler {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        $this->checkForBackendUrls($uri, $crawler);

        return $crawler;
    }

    /**
     * {@inheritdoc}
     */
    protected function isContentResponse($content)
    {
        // no hash navigation at frontend
        return parent::isContentResponse($content) && !$this->isFrontendUri($this->request->getUri());
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
