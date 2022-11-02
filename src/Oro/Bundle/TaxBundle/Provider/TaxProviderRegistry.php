<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of tax providers.
 */
class TaxProviderRegistry implements ResetInterface
{
    /** @var string[] */
    private $providerNames;

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var ConfigManager */
    private $configManager;

    /** @var TaxProviderInterface[]|null */
    private $providers;

    /**
     * @param string[]           $providerNames
     * @param ContainerInterface $providerContainer
     * @param ConfigManager      $configManager
     */
    public function __construct(
        array $providerNames,
        ContainerInterface $providerContainer,
        ConfigManager $configManager
    ) {
        $this->providerNames = $providerNames;
        $this->providerContainer = $providerContainer;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->providers = null;
    }

    /**
     * Gets all tax providers.
     *
     * @return TaxProviderInterface[] [provider name => provider, ...]
     */
    public function getProviders(): array
    {
        if (null === $this->providers) {
            $this->providers = [];
            foreach ($this->providerNames as $name) {
                /** @var TaxProviderInterface $provider */
                $provider = $this->providerContainer->get($name);
                if ($provider->isApplicable()) {
                    $this->providers[$name] = $provider;
                }
            }
        }

        return $this->providers;
    }

    /**
     * Gets a tax provider by its name.
     *
     * @throws \LogicException if a provider with the given name not found
     */
    public function getProvider(string $name): TaxProviderInterface
    {
        $provider = null;
        if (null === $this->providers) {
            if ($this->providerContainer->has($name)) {
                /** @var TaxProviderInterface $foundProvider */
                $foundProvider = $this->providerContainer->get($name);
                if ($foundProvider->isApplicable()) {
                    $provider = $foundProvider;
                }
            }
        } elseif (isset($this->providers[$name])) {
            $provider = $this->providers[$name];
        }

        if (null === $provider) {
            throw new \LogicException(
                sprintf('Tax provider with name "%s" does not exist', $name)
            );
        }

        return $provider;
    }

    /**
     * Retrieves a tax provider, currently enabled in the system config.
     */
    public function getEnabledProvider(): TaxProviderInterface
    {
        return $this->getProvider($this->configManager->get('oro_tax.tax_provider'));
    }
}
