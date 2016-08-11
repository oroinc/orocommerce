<?php

namespace OroB2B\Bundle\WebsiteBundle\Twig;

use OroB2B\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsitePathExtension extends \Twig_Extension
{
    const NAME = 'orob2b_website_path';
    
    /**
     * @var WebsiteUrlResolver
     */
    protected $websiteUrlResolver;

    /**
     * @param WebsiteUrlResolver $websiteUrlResolver
     */
    public function __construct(WebsiteUrlResolver $websiteUrlResolver)
    {
        $this->websiteUrlResolver = $websiteUrlResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'website_path' => new \Twig_SimpleFunction(
                $this,
                'getWebsitePath'
            ),
            'website_secure_path' => new \Twig_SimpleFunction(
                $this,
                'getWebsiteSecurePath'
            )
        ];
    }

    /**
     * @param string $route
     * @param array $routeParams
     * @param Website|null $website
     * @return string
     */
    public function getWebsitePath($route, array $routeParams, Website $website = null)
    {
        return $this->websiteUrlResolver->getWebsitePath($route, $routeParams, $website);
    }

    /**
     * @param string $route
     * @param array $routeParams
     * @param Website|null $website
     * @return string
     */
    public function getWebsiteSecurePath($route, array $routeParams, Website $website = null)
    {
        return $this->websiteUrlResolver->getWebsiteSecurePath($route, $routeParams, $website);
    }
}
