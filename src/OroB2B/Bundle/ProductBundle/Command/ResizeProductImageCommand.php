<?php

namespace OroB2B\Bundle\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

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
            ->addOption(self::OPTION_FORCE)
            ->setDescription('Resize Product Image (create resized images for all image types)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
            throw new \Exception('ProductImage doesn\'t exists');
        }

        $container->get('oro_layout.provider.image_filter')->load();
        $image  = $productImage->getImage();

        foreach ($this->getDimensionsForProductImage($productImage) as $dimension) {
            $filterName = (string) $dimension;
            $this->resizeImage($image,$filterName);
        }
    }

    /**
     * @param File $image
     * @param string $filterName
     */
    private function resizeImage(File $image, $filterName)
    {
        $container = $this->getContainer();
        $attachmentManager = $container->get('oro_attachment.manager');
        $path = $container->getParameter('liip_imagine.web_root') . $attachmentManager->getFilteredImageUrl($image, $filterName);

        $binary = $container->get('liip_imagine')->load($attachmentManager->getContent($image));
        $filteredBinary = $container->get('liip_imagine.filter.manager')->applyFilter($binary, $filterName);

        $response = new Response($filteredBinary, Response::HTTP_OK, ['Content-Type' => $image->getMimeType()]);

        $container->get('liip_imagine.cache.manager')->store($response, $path, $filterName);
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
