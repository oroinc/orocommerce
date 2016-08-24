<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;

use Prophecy\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\MemoryOutput;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Command\ResizeProductImageCommand;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ResizeProductImageCommandTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_CLASS = 'ProductImage';
    const PRODUCT_IMAGE_ID = 1;
    const FORCE_OPTION = false;
    const IMAGE_ID = 2;

    /**
     * @var ResizeProductImageCommand
     */
    protected $command;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ImageFilterLoader
     */
    protected $imageFilterLoader;

    /**
     * @var ImageResizer
     */
    protected $imageResizer;

    /**
     * @var ProductImageRepository
     */
    protected $productImageRepository;

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    public function setUp()
    {
        $this->imageTypeProvider = $this->prophesize(ImageTypeProvider::class);
        $this->imageFilterLoader = $this->prophesize(ImageFilterLoader::class);
        $this->imageResizer = $this->prophesize(ImageResizer::class);

        $this->productImageRepository = $this->prophesize(ProductImageRepository::class);

        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);
        $this->doctrineHelper
            ->getEntityRepositoryForClass(self::PRODUCT_IMAGE_CLASS)
            ->willReturn($this->productImageRepository);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('oro_entity.doctrine_helper')->willReturn($this->doctrineHelper);
        $container->get('oro_layout.loader.image_filter')->willReturn($this->imageFilterLoader);
        $container->get('oro_attachment.image_resizer')->willReturn($this->imageResizer);
        $container->get('oro_layout.provider.image_type')->willReturn($this->imageTypeProvider);
        $container->getParameter('orob2b_product.entity.product_image.class')->willReturn(self::PRODUCT_IMAGE_CLASS);

        $this->command = new ResizeProductImageCommand();
        $this->command->setContainer($container->reveal());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoProductImageFound()
    {
        $this->productImageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn(null);

        $this->command->run($this->prepareInput(), new MemoryOutput());
    }

    public function testResizeImage()
    {
        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(self::IMAGE_ID);

        $productImage = $this->prophesize(StubProductImage::class);
        $productImage->getImage()->willReturn($image->reveal());
        $productImage->getTypes()->willReturn(['main', 'listing']);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        $this->imageTypeProvider->getImageTypes()->willReturn([
            'main' => new ThemeImageType('name1', 'label1', [
                new ThemeImageTypeDimension('original', null, null),
                new ThemeImageTypeDimension('large', 1000, 1000)
            ]),
            'listing' => new ThemeImageType('name2', 'label2', [
                new ThemeImageTypeDimension('small', 100, 100),
                new ThemeImageTypeDimension('large', 1000, 1000)
            ]),
            'additional' => new ThemeImageType('name3', 'label3', [])
        ]);

        $this->imageFilterLoader->load()->shouldBeCalled();
        $this->productImageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn($productImage->reveal());

        $this->imageResizer->resizeImage($image, 'original', self::FORCE_OPTION)->willReturn(true);
        $this->imageResizer->resizeImage($image, 'large', self::FORCE_OPTION)->willReturn(true);
        $this->imageResizer->resizeImage($image, 'small', self::FORCE_OPTION)->willReturn(false);

        $this->command->run($this->prepareInput(), $this->prepareOutput([
            'Resized image #2 for filter [original] successfully created.',
            'Resized image #2 for filter [large] successfully created.',
            'Resized image #2 for filter [small] already exists.'
        ]));
    }

    /**
     * @return object
     */
    protected function prepareInput()
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getArgument('productImageId')->willReturn(self::PRODUCT_IMAGE_ID);
        $input->getOption(ResizeProductImageCommand::OPTION_FORCE)->willReturn(self::FORCE_OPTION);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument('command')->shouldBeCalled();
        $input->validate()->shouldBeCalled();

        return $input->reveal();
    }

    private function prepareOutput(array $messages)
    {
        $output = $this->prophesize(OutputInterface::class);

        foreach ($messages as $message) {
            $output->writeln($message)->shouldBeCalled();
        }

        return $output->reveal();
    }
}
