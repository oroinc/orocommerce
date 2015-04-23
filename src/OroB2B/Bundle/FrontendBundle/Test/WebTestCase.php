<?php

namespace OroB2B\Bundle\FrontendBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var Client
     */
    protected static $clientInstance;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Init Client
     *
     * @param array $options
     * @param array $server
     */
    protected function initClient($options = [], $server = [])
    {
        $this->client = static::createClient($options, $server);
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = array())
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        $kernel = new static::$class(
            isset($options['environment']) ? $options['environment'] : 'test',
            isset($options['debug']) ? $options['debug'] : true
        );

        $kernel->setApplication('frontend');

        return $kernel;
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     */
    protected function getUrl($name, $parameters = [], $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($name, $parameters, $absolute);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->client->getContainer();
    }
}
