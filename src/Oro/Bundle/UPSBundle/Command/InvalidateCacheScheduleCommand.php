<?php

namespace Oro\Bundle\UPSBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvalidateCacheScheduleCommand extends ContainerAwareCommand
{
    const NAME = 'oro:cron:shipping-price-cache-invalidate:schedule';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Invalidate Shipping Price Cache by schedule')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'UPS Transport Id'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $transportId = $input->getOption('id');
        if (!filter_var($transportId, FILTER_VALIDATE_INT)) {
            $output->writeln('<error>No UPS Transport identifier defined</error>');
            return;
        }

        $container = $this->getContainer();
        $transport = $this->getRepository(UPSTransport::class)->find($transportId);
        if ($transport && $transport->getInvalidateCacheAt()) {
            $savedYear = $transport->getInvalidateCacheAt()->format('Y');
            if ($savedYear === gmdate('Y')) {
                $container->get('oro_ups.shipping_price_cache')->deleteAll($transportId);
                $container->get('oro_shipping.shipping_price.provider.cache')->deleteAllPrices();
                $output->writeln('<info>Shipping Cache was successfully cleared</info>');
                return;
            }
        }
        $output->writeln('<error>Shipping Cache was not cleared</error>');
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }
}
