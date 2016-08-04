<?php

namespace OroB2B\Bundle\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ResizeProductImageCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'product:image:resize';
    const OPTION_FORCE = 'force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                'productImageId',
                InputArgument::REQUIRED,
                'ID of ProductImage entity'
            )
            ->addOption(self::OPTION_FORCE, null, null, 'Overwrite existing images')
            ->setDescription('Resize Product Image (create resized images for all image types)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool) $input->getOption(self::OPTION_FORCE);

        $container         = $this->getContainer();
        $productImage      = null;
        $productImageId    = (int) $input->getArgument('productImageId');
        $productImageClass = $container->getParameter('orob2b_product.entity.product_image.class');

        /** @var ProductImage $productImage */
        $productImage = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass($productImageClass)
            ->getRepository($productImageClass)
            ->find($productImageId);

        if (!$productImage) {
            throw new \InvalidArgumentException('ProductImage doesn\'t exists');
        }

        $container->get('oro_layout.provider.image_filter')->load();
        $image  = $productImage->getImage();

        foreach ($this->getDimensionsForProductImage($productImage) as $dimension) {
            $this->resizeImage($image, $dimension->getName(), $force, $output);
        }
    }

    /**
     * @param File $image
     * @param string $filterName
     * @param bool $force
     * @param OutputInterface $output
     */
    private function resizeImage(File $image, $filterName, $force, $output)
    {
        $resizer = $this->getContainer()->get('oro_attachment.image_resizer');

        if ($resizer->resizeImage($image, $filterName, $force)) {
            $message = 'Resized image #%d for filter [%s] successfully created.';
        } else {
            $message = 'Resized image #%d for filter [%s] already exists.';
        }

        $output->writeln(sprintf($message, $image->getId(), $filterName));
    }

    /**
     * @param ProductImage $productImage
     * @return ThemeImageTypeDimension[]
     */
    private function getDimensionsForProductImage(ProductImage $productImage)
    {
        $dimensions = [];
        $imageTypeProvider = $this->getContainer()->get('oro_layout.provider.image_type');
        $allImageTypes = $imageTypeProvider->getImageTypes();

        foreach ($productImage->getTypes() as $imageType) {
            if (isset($allImageTypes[$imageType])) {
                $dimensions = array_merge($dimensions, $allImageTypes[$imageType]->getDimensions());
            }
        }

        return $dimensions;
    }
}
