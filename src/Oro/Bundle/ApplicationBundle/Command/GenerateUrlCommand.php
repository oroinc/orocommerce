<?php

namespace Oro\Bundle\ApplicationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GenerateUrlCommand extends ContainerAwareCommand
{
    const NAME = 'oro:application:generate:url';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Generate URL based on route name and parameters')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Route name'
            )
            ->addArgument(
                'parameters',
                InputArgument::OPTIONAL,
                'Route parameters',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // process arguments
        $name = $input->getArgument('name');
        $parameters = $input->getArgument('parameters');
        if (is_string($parameters)) {
            $parameters = json_decode($parameters, true);
        }

        // get container data
        $container = $this->getContainer();
        /** @var \AppKernel $kernel */
        $kernel = $container->get('kernel');
        $applications = $container->getParameter('application_hosts');

        // update request context
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        $routerContext = $router->getContext();
        $routerContext->fromRequest(Request::create($applications[$kernel->getApplication()]));
        $routerContext->setBaseUrl(rtrim($routerContext->getPathInfo(), '/'));
        $routerContext->setPathInfo('');

        // generate URL
        $url = $router->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        $output->writeln($url);
    }
}
