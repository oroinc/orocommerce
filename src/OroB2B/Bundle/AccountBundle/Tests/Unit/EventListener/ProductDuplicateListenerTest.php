<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\EventListener\ProductDuplicateListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit_Framework_TestCase
{
    const VISIBILITY_TO_ALL_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility';
    const VISIBILITY_TO_ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility';
    const VISIBILITY_TO_ACCOUNT_GROUP_CLASS =
        'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility';

    /** @var  Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var  ProductDuplicateListener */
    protected $productDuplicateListener;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productDuplicateListener = new ProductDuplicateListener($this->doctrine);
        $this->productDuplicateListener->setVisibilityToAllClassName(self::VISIBILITY_TO_ALL_CLASS);
        $this->productDuplicateListener->setVisibilityAccountClassName(self::VISIBILITY_TO_ACCOUNT_CLASS);
        $this->productDuplicateListener->setVisibilityAccountGroupClassName(self::VISIBILITY_TO_ACCOUNT_GROUP_CLASS);
    }

    public function testOnDuplicateProduct()
    {
        $product = new Product();
        $sourceProduct = clone $product;

        /** @var ProductDuplicateAfterEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getProduct')->willReturn($product);
        $event->expects($this->once())->method('getSourceProduct')->willReturn($sourceProduct);

        $visibilityToAll = new ProductVisibility();
        $visibilityToAccount = new AccountProductVisibility();
        $visibilityToAccountGroup = new AccountGroupProductVisibility();

        /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject $repoAll */
        $repoVisibilityAll = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repoVisibilityAccount = clone $repoVisibilityAll;
        $repoVisibilityAccountGroup = clone $repoVisibilityAll;

        $repoVisibilityAll->expects($this->once())
            ->method('findBy')
            ->with(['product' => $sourceProduct])
            ->willReturn([$visibilityToAll]);

        $repoVisibilityAccount->expects($this->once())
            ->method('findBy')
            ->with(['product' => $sourceProduct])
            ->willReturn([$visibilityToAccount]);

        $repoVisibilityAccountGroup->expects($this->once())
            ->method('findBy')
            ->with(['product' => $sourceProduct])
            ->willReturn([$visibilityToAccountGroup]);
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())->method('getRepository')->willReturnMap(
            [
                [self::VISIBILITY_TO_ALL_CLASS, $repoVisibilityAll],
                [self::VISIBILITY_TO_ACCOUNT_CLASS, $repoVisibilityAccount],
                [self::VISIBILITY_TO_ACCOUNT_GROUP_CLASS, $repoVisibilityAccountGroup],
            ]
        );

        $duplicatedVisibilityToAll = clone $visibilityToAll;
        $duplicatedVisibilityToAccount = clone $visibilityToAccount;
        $duplicatedVisibilityToAccountGroup = clone $visibilityToAccountGroup;

        $duplicatedVisibilityToAll->setProduct($product);
        $duplicatedVisibilityToAccount->setProduct($product);
        $duplicatedVisibilityToAccountGroup->setProduct($product);

        $manager->expects($this->any())->method('persist')->willReturnMap(
            [
                [$duplicatedVisibilityToAll],
                [$duplicatedVisibilityToAccount],
                [$duplicatedVisibilityToAccountGroup],
            ]
        );

        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($product))
            ->willReturn($manager);

        $this->productDuplicateListener->onDuplicateProduct($event);
    }
}
