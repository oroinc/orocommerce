<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Generate Sluggable URLs for given route and parameters
 */
class SluggableUrlGenerator implements UrlGeneratorInterface
{
    const DEFAULT_LOCALIZATION_ID = 0;
    const CONTEXT_DELIMITER = '_item';
    const CONTEXT_TYPE = 'context_type';
    const CONTEXT_DATA = 'context_data';

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * @var ContextUrlProviderRegistry
     */
    private $contextUrlProvider;

    /**
     * @var SluggableUrlProviderInterface
     */
    private $sluggableUrlProvider;

    /**
     * @var UserLocalizationManager
     */
    private $userLocalizationManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var bool|null
     */
    private $sluggableUrlsEnabled;

    /**
     * @param SluggableUrlProviderInterface $sluggableUrlProvider
     * @param ContextUrlProviderRegistry $contextUrlProvider
     * @param UserLocalizationManager $userLocalizationManager
     */
    public function __construct(
        SluggableUrlProviderInterface $sluggableUrlProvider,
        ContextUrlProviderRegistry $contextUrlProvider,
        UserLocalizationManager $userLocalizationManager
    ) {
        $this->sluggableUrlProvider = $sluggableUrlProvider;
        $this->contextUrlProvider = $contextUrlProvider;
        $this->userLocalizationManager = $userLocalizationManager;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        UrlParameterHelper::normalizeNumericTypes($parameters);

        if ($referenceType === self::ABSOLUTE_PATH || $referenceType === false) {
            return $this->generateSluggableUrl($name, $parameters);
        }

        return $this->generator->generate($name, $parameters, $referenceType);
    }

    /**
     * @param string $name
     * @param mixed $parameters
     * @return string
     */
    private function generateSluggableUrl($name, $parameters)
    {
        $contextUrl = $this->getContextUrl($parameters);
        if (!$this->isSluggableUrlsEnabled()) {
            return $this->addContextUrl($this->generator->generate($name, $parameters), $contextUrl);
        }

        $localizationId = $this->getLocalizationId();

        $url = null;

        $this->sluggableUrlProvider->setContextUrl($contextUrl);

        $url = $this->sluggableUrlProvider->getUrl($name, $parameters, $localizationId);
        // Fallback to default localization
        if (!$url) {
            $url = $this->sluggableUrlProvider->getUrl($name, $parameters, self::DEFAULT_LOCALIZATION_ID);
        }

        // If no Slug based URL is available - generate URL with base generator logic
        if (!$url) {
            $url = $this->generator->generate($name, $parameters);
        }

        return $this->addContextUrl($url, $contextUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->generator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->generator->getContext();
    }

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function setBaseGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param array $parameters
     * @return string|null
     */
    private function getContextUrl(array &$parameters)
    {
        if (array_key_exists(self::CONTEXT_TYPE, $parameters) && array_key_exists(self::CONTEXT_DATA, $parameters)) {
            $contextType = $parameters[self::CONTEXT_TYPE];
            $contextData = $parameters[self::CONTEXT_DATA];
            unset($parameters[self::CONTEXT_TYPE], $parameters[self::CONTEXT_DATA]);

            $url = $this->contextUrlProvider->getUrl($contextType, $contextData);

            return trim($url, '/');
        }

        return null;
    }

    /**
     * @param string $url
     * @param string $contextUrl
     * @return string
     */
    private function addContextUrl($url, $contextUrl)
    {
        $baseUrl = $this->getContext()->getBaseUrl();
        if ($baseUrl) {
            if (strpos($url, $baseUrl) === 0) {
                $url = substr($url, strlen($baseUrl));
            }
            $urlParts = [trim($baseUrl, '/')];
        }

        if ($contextUrl) {
            $urlParts[] = trim($contextUrl, '/');
            $urlParts[] = self::CONTEXT_DELIMITER;
        }
        $urlParts[] = ltrim($url, '/');

        return '/' . implode('/', $urlParts);
    }

    /**
     * @return null|int
     */
    private function getLocalizationId()
    {
        $localization = $this->userLocalizationManager->getCurrentLocalization();
        return $localization ? $localization->getId() : null;
    }

    /**
     * @return bool
     */
    private function isSluggableUrlsEnabled(): bool
    {
        if ($this->sluggableUrlsEnabled === null) {
            $this->sluggableUrlsEnabled = $this->configManager->get('oro_redirect.enable_direct_url');
        }

        return $this->sluggableUrlsEnabled;
    }
}
