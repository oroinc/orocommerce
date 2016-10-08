<?php

namespace Oro\Bundle\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

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
            ->addOption(self::OPTION_FORCE, null, null, 'Overwrite existing images')
            ->setDescription(<<<DESC
Resize All Product Images (the command only adds jobs to a queue, ensure the oro:message-queue:consume command 
is running to get images resized)
DESC
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$productImages = $this->getProductImages()) {
            $output->writeln('No product images found.');

            return;
        }

        foreach ($productImages as $productImage) {
            $this->getEventDispatcher()->dispatch(
                ProductImageResizeEvent::NAME,
                new ProductImageResizeEvent($productImage, $this->getForceOption($input))
            );
        }

        $output->writeln(sprintf('%d product image(s) successfully queued for resize.', count($productImages)));
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }

    /**
     * @return ProductImage[]
     */
    protected function getProductImages()
    {
        return $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass($this->getContainer()->getParameter('oro_product.entity.product_image.class'))
            ->findAll();
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function getForceOption(InputInterface $input)
    {
        return (bool) $input->getOption(self::OPTION_FORCE);
    }
}
