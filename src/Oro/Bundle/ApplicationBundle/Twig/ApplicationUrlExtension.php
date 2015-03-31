<?php

namespace Oro\Bundle\ApplicationBundle\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class ApplicationUrlExtension extends \Twig_Extension
{
    const NAME = 'oro_application_application_url_extension';

    /**
     * @var string
     */
    protected $kernelEnvironment;

    /**
     * @var array
     */
    protected $applicationHosts;

    /**
     * @param string $kernelEnvironment
     * @param array $applicationHosts
     */
    public function __construct($kernelEnvironment, array $applicationHosts)
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->applicationHosts = $applicationHosts;
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
            new \Twig_SimpleFunction('application_url', [$this, 'getApplicationUrl'])
        ];
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param bool $schemeRelative
     * @return string
     */
    public function getApplicationUrl($name, array $parameters = [], $schemeRelative = false)
    {
        if (!isset($parameters['application'])) {
            throw new \InvalidArgumentException('Parameters must have required element `application`.');
        }

        $applicationName = $parameters['application'];
        unset($parameters['application']);

        $kernel = $this->getKernel($applicationName);
        $router = $kernel->getContainer()->get('router');

        $routerContext = $router->getContext();
        $routerContext->fromRequest(Request::create($this->getHost($applicationName)));
        $routerContext->setBaseUrl(rtrim($routerContext->getPathInfo(), '/'));
        $routerContext->setPathInfo('');

        return $router->generate(
            $name,
            $parameters,
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param  string $applicationName
     * @return \AppKernel
     * @throws \Exception
     */
    protected function getKernel($applicationName)
    {
        $kernel = new \AppKernel($applicationName, $this->kernelEnvironment, false);
        $kernel->loadClassCache();
        $kernel->boot();

        return $kernel;
    }

    /**
     * @param string $applicationName
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getHost($applicationName)
    {
        if (!isset($this->applicationHosts[$applicationName])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name of the application is not a valid. Allowed the following names: %s.',
                    implode(', ', array_keys($this->applicationHosts))
                )
            );
        }

        return $this->applicationHosts[$applicationName];
    }
}
