<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ImageResizeMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_ID = 1;
    const FORCE_OPTION = false;

    /**
     * @var EntityRepository
     */
    protected $imageRepository;

    /**
     * @var ImageFilterLoader
     */
    protected $filterLoader;

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var ImageResizer
     */
    protected $imageResizer;

    /**
     * @var array
     */
    protected static $validData = [
        'productImageId' => self::PRODUCT_IMAGE_ID,
        'force' => self::FORCE_OPTION
    ];

    /**
     * @var ImageResizeMessageProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->imageRepository = $this->prophesize(EntityRepository::class);
        $this->filterLoader = $this->prophesize(ImageFilterLoader::class);
        $this->imageTypeProvider = $this->prophesize(ImageTypeProvider::class);
        $this->imageResizer = $this->prophesize(ImageResizer::class);

        $this->processor = new ImageResizeMessageProcessor(
            $this->imageRepository->reveal(),
            $this->filterLoader->reveal(),
            $this->imageTypeProvider->reveal(),
            $this->imageResizer->reveal()
        );
    }

    public function testProcessInvalidJson()
    {
        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareMessage('not valid json'),
            $this->prepareSession()
        ));
    }

    public function testProcessInvalidData()
    {
        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareMessage(JSON::encode(['abc'])),
            $this->prepareSession()
        ));
    }

    public function testProcessProductImageNotFound()
    {
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn(null);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidData()
    {
        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(self::PRODUCT_IMAGE_ID);

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

        $this->filterLoader->load()->shouldBeCalled();
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn($productImage->reveal());

        $this->imageResizer->resizeImage($image, 'original', self::FORCE_OPTION)->willReturn(true);
        $this->imageResizer->resizeImage($image, 'large', self::FORCE_OPTION)->willReturn(true);
        $this->imageResizer->resizeImage($image, 'small', self::FORCE_OPTION)->willReturn(false);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    /**
     * @param string $body
     * @return DbalMessage
     */
    protected function prepareMessage($body)
    {
        $message = new DbalMessage();
        $message->setBody($body);

        return $message;
    }

    /**
     * @return object|SessionInterface
     */
    private function prepareSession()
    {
        return $this->prophesize(SessionInterface::class)->reveal();
    }

    /**
     * @return DbalMessage
     */
    protected function prepareValidMessage()
    {
        return $this->prepareMessage(JSON::encode(self::$validData));
    }
}
