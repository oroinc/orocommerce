<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;

use Prophecy\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Command\ResizeAllProductImagesCommand;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ResizeAllProductImagesCommandTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_CLASS = 'ProductImage';
    const FORCE_OPTION = false;

    /**
     * @var ResizeAllProductImagesCommand
     */
    protected $command;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ProductImageRepository
     */
    protected $productImageRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->productImageRepository = $this->prophesize(ProductImageRepository::class);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);
        $this->doctrineHelper
            ->getEntityRepositoryForClass(self::PRODUCT_IMAGE_CLASS)
            ->willReturn($this->productImageRepository);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('oro_entity.doctrine_helper')->willReturn($this->doctrineHelper);
        $container->get('event_dispatcher')->willReturn($this->eventDispatcher);
        $container->getParameter('oro_product.entity.product_image.class')->willReturn(self::PRODUCT_IMAGE_CLASS);

        $this->command = new ResizeAllProductImagesCommand();
        $this->command->setContainer($container->reveal());
    }

    public function testNoProductImages()
    {
        $this->productImageRepository->findAll()->willReturn([]);

        $this->command->run($this->prepareInput(), $this->prepareOutput('No product images found.'));
    }

    public function testResizeAllImages()
    {
        $productImage1 = new ProductImage();

        $this->productImageRepository->findAll()->willReturn([
            $productImage1,
            new ProductImage(),
            new ProductImage()
        ]);

        $asserter = $this;

        $this->eventDispatcher
            ->dispatch(ProductImageResizeEvent::NAME, Argument::type(ProductImageResizeEvent::class))
            ->shouldBeCalledTimes(3)
            ->will(function ($args) use ($asserter, $productImage1) {
                /** @var ProductImageResizeEvent $event */
                $event = $args[1];
                $asserter->assertEquals($productImage1, $event->getProductImage());
                $asserter->assertEquals(self::FORCE_OPTION, $event->getForceOption());
            });

        $this->command->run(
            $this->prepareInput(),
            $this->prepareOutput('3 product images successfully queued for resize.')
        );
    }

    /**
     * @return object
     */
    protected function prepareInput()
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getOption(ResizeAllProductImagesCommand::OPTION_FORCE)->willReturn(self::FORCE_OPTION);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument('command')->shouldBeCalled();
        $input->validate()->shouldBeCalled();

        return $input->reveal();
    }

    /**
     * @param string $message
     * @return object
     */
    private function prepareOutput($message)
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->writeln($message)->shouldBeCalled();

        return $output->reveal();
    }
}
