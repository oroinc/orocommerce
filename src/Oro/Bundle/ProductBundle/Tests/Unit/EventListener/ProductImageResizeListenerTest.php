<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Prophecy\Argument;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Command\ResizeProductImageCommand;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;

class ProductImageResizeListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_ID = 1;

    /**
     * @var ProductImageResizeListener
     */
    protected $listener;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManager::class);

        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);
        $this->doctrineHelper->getEntityManagerForClass(Job::class)->willReturn($this->em);

        $this->listener = new ProductImageResizeListener($this->doctrineHelper->reveal());
    }

    public function testResizeProductImage()
    {
        $event = $this->prepareEvent();
        $this->setEntityManagerExpectations([self::PRODUCT_IMAGE_ID]);

        $this->listener->resizeProductImage($event);
    }

    public function testResizeProductImageWithForceOption()
    {
        $event = $this->prepareEvent($forceOption = true);
        $this->setEntityManagerExpectations([self::PRODUCT_IMAGE_ID, '--force']);

        $this->listener->resizeProductImage($event);
    }

    /**
     * @param bool $forceOption
     * @return ProductImageResizeEvent
     */
    protected function prepareEvent($forceOption = false)
    {
        $productImage = $this->prophesize(ProductImage::class);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        return new ProductImageResizeEvent($productImage->reveal(), $forceOption);
    }

    /**
     * @param array $expectedJobArgs
     */
    protected function setEntityManagerExpectations(array $expectedJobArgs)
    {
        $asserter = $this;

        $this->em->persist(Argument::type(Job::class))->shouldBeCalled()->will(
            function ($args) use ($expectedJobArgs, $asserter) {
                /** @var Job $job */
                $job = $args[0];
                $jobArgs = $job->getArgs();
                $asserter->assertEquals(ResizeProductImageCommand::COMMAND_NAME, $job->getCommand());
                $asserter->assertCount(count($expectedJobArgs), $jobArgs);

                foreach ($expectedJobArgs as $index => $value) {
                    $asserter->assertEquals($value, $jobArgs[$index]);
                }
            }
        );
        $this->em->flush(Argument::type(Job::class))->shouldBeCalled();
    }
}
