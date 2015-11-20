<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

class ValidateActionConfigurationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:action:configuration:validate')
            ->setDescription('Validate action configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $output->writeln('Load actions ...');

        /* @var $provider ActionConfigurationProvider */
        $provider = $container->get('oro_action.configuration.provider');
        $configuration = $provider->getActionConfiguration(true);

        if ($configuration) {
            $errors = $provider->getConfigurationErrors();

            $output->writeln(sprintf('Found %d action(s) with %d error(s):', count($configuration), count($errors)));

            foreach ($errors as $error) {
                $output->writeln($error);
            }
        } else {
            $output->writeln('No actions found.');
        }
    }
}
