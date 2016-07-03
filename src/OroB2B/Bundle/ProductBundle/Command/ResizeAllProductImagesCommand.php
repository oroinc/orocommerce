<?php

namespace OroB2B\Bundle\ProductBundle\Command;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ResizeAllProductImagesCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'product:image:resize-all';
    const OPTION_FORCE = 'force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addOption(self::OPTION_FORCE)
            ->setDescription('Resize All Product Images (async)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool) $input->getOption(self::OPTION_FORCE);

        if (!$force) {
            $output->writeln(
                '<comment>ATTENTION</comment>: To resize all product images ' .
                'run command with <info>--' . self::OPTION_FORCE . '</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --%s</info>', $this->getName(), self::OPTION_FORCE));

            return;
        }

        $container = $this->getContainer();
        $productImageClass = $container->getParameter('orob2b_product.entity.product_image.class');

        /** @var ProductImage[] $productImages */
        $productImages = $this
            ->getManagerForClass($productImageClass)
            ->getRepository($productImageClass)
            ->findAll();

        if (!$productImageCount = count($productImages)) {
            $output->writeln('No product images found.');

            return;
        }

        $jobManager = $this->getManagerForClass(Job::class);
        foreach ($productImages as $productImage) {
            $resizeJob = new Job(ResizeProductImageCommand::COMMAND_NAME, [$productImage->getId()]);
            $jobManager->persist($resizeJob);
        }
        $jobManager->flush();
        $output->writeln(sprintf('%d product images successfully queued for resize.', $productImageCount));
    }

    /**
     * @param $class
     * @return EntityManager|null
     */
    private function getManagerForClass($class)
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass($class);
    }
}
