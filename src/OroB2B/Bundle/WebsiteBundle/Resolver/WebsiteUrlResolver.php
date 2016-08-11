<?php

namespace OroB2B\Bundle\WebsiteBundle\Resolver;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteUrlResolver
{
    /**
     * @param ConfigManager $configManager
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(ConfigManager $configManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->configManager = $configManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $rout
     * @param array $parameters
     * @param Website $website
     * @return string
     */
    public function getWebsitePath($rout, array $parameters, Website $website)
    {
        $url = $this->configManager->get('oro_b2b_website.url', false, false, $website)
            ? : $this->configManager->get('oro_b2b_website.url', false, false);
        
        return $this->preparePath($url, $rout, $parameters);
    }

    /**
     * @param string $rout
     * @param array $parameters
     * @param Website $website
     * @return string
     */
    public function getWebsiteSecurePath($rout, array $parameters, Website $website)
    {
        $url = $this->configManager->get('oro_b2b_website.secure_url', false, false, $website)
            ? : $this->configManager->get('oro_b2b_website.url', false, false, $website)
            ? : $this->configManager->get('oro_b2b_website.url', false, false);

        return $this->preparePath($url, $rout, $parameters);
    }

    /**
     * @param string $url
     * @param string $rout
     * @param array $parameters
     * @return string
     */
    protected function preparePath($url, $rout, array $parameters)
    {
        return rtrim($url, '/') . $this->urlGenerator->generate($rout, $parameters);
    }
}
