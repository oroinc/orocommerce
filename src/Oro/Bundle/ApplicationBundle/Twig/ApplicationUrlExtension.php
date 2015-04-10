<?php

namespace Oro\Bundle\ApplicationBundle\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

use Oro\Bundle\ApplicationBundle\Command\GenerateUrlCommand;

class ApplicationUrlExtension extends \Twig_Extension
{
    const NAME = 'oro_application_application_url_extension';

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $applicationHosts;

    /**
     * @param KernelInterface $kernel
     * @param array $applicationHosts
     */
    public function __construct(KernelInterface $kernel, array $applicationHosts)
    {
        $this->kernel = $kernel;
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
            new \Twig_SimpleFunction('application_url', [$this, 'getApplicationUrl']),
            new \Twig_SimpleFunction('application_host', [$this, 'getHost'])
        ];
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function getApplicationUrl($name, array $parameters)
    {
        if (!array_key_exists('application', $parameters)) {
            throw new \InvalidArgumentException('Parameters must have required element `application`.');
        }

        $applicationName = $parameters['application'];
        unset($parameters['application']);

        $processBuilder = new ProcessBuilder();
        $processBuilder->add($this->getPhp())
            ->add($this->kernel->getRootDir() . DIRECTORY_SEPARATOR . 'console')
            ->add(GenerateUrlCommand::NAME)
            ->add($name)
            ->add(json_encode($parameters))
            ->add('--app=' . $applicationName)
            ->add('--env=' . $this->kernel->getEnvironment());

        $process = $processBuilder->getProcess();
        if ($process->run() !== 0) {
            throw new \LogicException('Invalid URL generation result');
        }

        return trim($process->getOutput());
    }

    /**
     * @return string
     * @throws \LogicException
     */
    protected function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();
        if (!$phpPath) {
            throw new \LogicException('The PHP executable could not be found.');
        }

        return $phpPath;
    }

    /**
     * @param string $applicationName
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getHost($applicationName)
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
