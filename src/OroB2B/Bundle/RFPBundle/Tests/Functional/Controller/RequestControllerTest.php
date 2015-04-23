<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RequstControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected static $container;

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return static::$container;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return KernelInterface
     */
    public function getKernel()
    {
        return static::$kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

        static::$kernel = static::createKernel($options);
        static::$kernel->setApplication('frontend');
        static::$kernel->boot();

        static::$container = static::$kernel->getContainer();

        $client = static::$kernel->getContainer()->get('test.client');
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = $this->createClient();
    }

    public function testLol()
    {
        var_dump($this->getContainer()->getParameter('orob2b_rfp.form.type.request.class'));
    }
}
