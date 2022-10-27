<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlProviderInterface;
use Oro\Component\Routing\UrlUtil;
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

    private UrlGeneratorInterface $generator;

    private ContextUrlProviderRegistry $contextUrlProvider;

    private SluggableUrlProviderInterface $sluggableUrlProvider;

    private LocalizationProviderInterface $localizationProvider;

    private ConfigManager $configManager;

    private ?bool $sluggableUrlsEnabled = null;

    public function __construct(
        SluggableUrlProviderInterface $sluggableUrlProvider,
        ContextUrlProviderRegistry $contextUrlProvider,
        LocalizationProviderInterface $localizationProvider,
        ConfigManager $configManager
    ) {
        $this->sluggableUrlProvider = $sluggableUrlProvider;
        $this->contextUrlProvider = $contextUrlProvider;
        $this->localizationProvider = $localizationProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH)
    {
        UrlParameterHelper::normalizeNumericTypes($parameters);

        if ($referenceType === self::ABSOLUTE_PATH) {
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

        $this->sluggableUrlProvider->setContextUrl($contextUrl);

        $url = $this->sluggableUrlProvider->getUrl($name, $parameters, $localizationId);
        // fallback to default localization
        if (!$url) {
            $url = $this->sluggableUrlProvider->getUrl($name, $parameters, self::DEFAULT_LOCALIZATION_ID);
        }
        // if no Slug based URL is available - generate URL with base generator logic
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
     * @param string|null $contextUrl
     * @return string
     */
    private function addContextUrl($url, $contextUrl)
    {
        $baseUrl = $this->getContext()->getBaseUrl();
        if ($contextUrl) {
            $url = UrlUtil::join($contextUrl, self::CONTEXT_DELIMITER, UrlUtil::getPathInfo($url, $baseUrl));
        }

        return UrlUtil::getAbsolutePath($url, $baseUrl);
    }

    private function getLocalizationId(): ?int
    {
        return $this->localizationProvider->getCurrentLocalization()?->getId();
    }

    private function isSluggableUrlsEnabled(): bool
    {
        if ($this->sluggableUrlsEnabled === null) {
            $this->sluggableUrlsEnabled = $this->configManager->get('oro_redirect.enable_direct_url');
        }

        return $this->sluggableUrlsEnabled;
    }
}
