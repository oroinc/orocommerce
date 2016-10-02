<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\Preprocessor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Expression\Preprocessor\ProductAssignmentRuleExpressionPreprocessor;

class ProductAssignmentRuleExpressionPreprocessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var ProductAssignmentRuleExpressionPreprocessor
     */
    protected $preprocessor;

    protected function setUp()
    {
        $registry = $this->getMock(ManagerRegistry::class);
        $this->em = $this->getMock(EntityManagerInterface::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->preprocessor = new ProductAssignmentRuleExpressionPreprocessor($registry);
    }

    public function testProcess()
    {
        $expression = 'pricelist[2].productAssignmentRule and product.id > 20 or pricelist[3].productAssignmentRule';

        $priceList2 = new PriceList();
        $priceList2->setProductAssignmentRule('product.category.id == 100');
        $priceList3 = new PriceList();
        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap(
                [
                    [PriceList::class, '2', $priceList2],
                    [PriceList::class, '3', $priceList3]
                ]
            );

        $this->assertEquals(
            'product.category.id == 100 and product.id > 20 or 1 == 1',
            $this->preprocessor->process($expression)
        );
    }
}
