<?php

namespace Oro\Bundle\DPDBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvalidateCacheScheduleCommand extends ContainerAwareCommand
{
    const NAME = 'oro:cron:dpd-cache-invalidate:schedule';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Invalidate DPD Cache by schedule')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'DPD Transport Id'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $transportId = $input->getOption('id');
        if (!filter_var($transportId, FILTER_VALIDATE_INT)) {
            $output->writeln('<error>No DPD Transport identifier defined</error>');

            return;
        }

        $container = $this->getContainer();
        $transport = $this->getRepository(DPDTransport::class)->find($transportId);
        if ($transport && $transport->getInvalidateCacheAt()) {
            $savedYear = $transport->getInvalidateCacheAt()->format('Y');
            if ($savedYear === gmdate('Y')) {
                $container->get('oro_dpd.zip_code_rules_cache')->deleteAll($transportId);
                $container->get('oro_shipping.shipping_price.provider.cache')->deleteAllPrices();
                $output->writeln('<info>Shipping Cache was successfully cleared</info>');

                return;
            }
        }
        $output->writeln('<error>Shipping Cache was not cleared</error>');
    }

    /**
     * @param string $className
     *
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }
}
