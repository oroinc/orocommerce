<?php

namespace OroB2B\Bundle\FrontendBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    /** Annotation names */
    const DB_ISOLATION_ANNOTATION = 'dbIsolation';

    /**
     * @var bool[]
     */
    private static $dbIsolation;

    /**
     * @var Client
     */
    protected static $clientInstance;

    /**
     * @var Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $refClass = new \ReflectionClass($this);
        foreach ($refClass->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        if (self::$clientInstance) {
            if (self::getDbIsolationSetting()) {
                self::$clientInstance->rollbackTransaction();
            }
            self::$clientInstance = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();

        $finder = new Finder();
        $finder->name('AppKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);

        if (count($results)) {
            $file  = current($results);
            $class = $file->getBasename('.php');

            require_once $file;
        } else {
            $class = parent::getKernelClass();
        }

        return $class;
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = array())
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        /** @var \AppKernel $kernel */
        $kernel = new static::$class(
            isset($options['environment']) ? $options['environment'] : 'test',
            isset($options['debug']) ? $options['debug'] : true
        );

        $kernel->setApplication('frontend');

        return $kernel;
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server  An array of server parameters
     * @param bool  $force If this option - true, will reset client on each initClient call
     *
     * @return Client A Client instance
     */
    protected function initClient(array $options = array(), array $server = array(), $force = false)
    {
        if ($force) {
            $this->resetClient();
        }

        if (!self::$clientInstance) {
            /** @var Client $client */
            $client = self::$clientInstance = static::createClient($options, $server);

            if (self::getDbIsolationSetting()) {
                $client->startTransaction();
            }
        } else {
            self::$clientInstance->setServerParameters($server);
        }

        $this->client = self::$clientInstance;
    }

    /**
     * Reset client and rollback transaction
     */
    protected function resetClient()
    {
        if (self::$clientInstance) {
            if (self::getDbIsolationSetting()) {
                self::$clientInstance->rollbackTransaction();
            }

            $this->client = null;
            self::$clientInstance = null;
        }
    }

    /**
     * Get value of dbIsolation option from annotation of called class
     *
     * @return bool
     */
    protected static function getDbIsolationSetting()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbIsolation[$calledClass])) {
            self::$dbIsolation[$calledClass] = self::isClassHasAnnotation($calledClass, self::DB_ISOLATION_ANNOTATION);
        }

        return self::$dbIsolation[$calledClass];
    }

    /**
     * @param string $className
     * @param string $annotationName
     *
     * @return bool
     */
    private static function isClassHasAnnotation($className, $annotationName)
    {
        $annotations = \PHPUnit_Util_Test::parseTestMethodAnnotations($className);
        return isset($annotations['class'][$annotationName]);
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
    protected function getUrl($name, $parameters = array(), $absolute = false)
    {
        return self::getContainer()->get('router')->generate($name, $parameters, $absolute);
    }

    /**
     * Get an instance of the dependency injection container.
     *
     * @return ContainerInterface
     */
    protected static function getContainer()
    {
        return static::getClientInstance()->getContainer();
    }

    /**
     * @return Client
     * @throws \BadMethodCallException
     */
    public static function getClientInstance()
    {
        if (!self::$clientInstance) {
            throw new \BadMethodCallException('Client instance is not initialized.');
        }

        return self::$clientInstance;
    }

    /**
     * Assert response is html and has status code
     *
     * @param Response $response
     * @param int      $statusCode
     */
    public static function assertHtmlResponseStatusCodeEquals(Response $response, $statusCode)
    {
        self::assertResponseStatusCodeEquals($response, $statusCode);
        self::assertResponseContentTypeEquals($response, 'text/html; charset=UTF-8');
    }

    /**
     * Assert response status code equals
     *
     * @param Response $response
     * @param int      $statusCode
     */
    public static function assertResponseStatusCodeEquals(Response $response, $statusCode)
    {
        \PHPUnit_Framework_TestCase::assertEquals(
            $statusCode,
            $response->getStatusCode()
        );
    }

    /**
     * Assert response content type equals
     *
     * @param Response $response
     * @param string   $contentType
     */
    public static function assertResponseContentTypeEquals(Response $response, $contentType)
    {
        \PHPUnit_Framework_TestCase::assertTrue(
            $response->headers->contains('Content-Type', $contentType),
            $response->headers
        );
    }
}
