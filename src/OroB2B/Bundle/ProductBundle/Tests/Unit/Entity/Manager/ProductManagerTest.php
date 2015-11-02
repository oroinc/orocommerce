<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Manager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductManagerTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'SomeClass';

    /** @var  RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var  ProductManager */
    protected $productManager;

    /** @var  RegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var  ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject */
    protected $modifier;

    public function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->modifier = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier');
        $this->productManager = new ProductManager(
            $this->eventDispatcher,
            $this->requestStack,
            $this->registry,
            $this->modifier
        );
        $this->productManager->setDataClass(self::DATA_CLASS);
    }

    /**
     * @dataProvider restrictQueryBuilderByProductVisibilityDataProvider
     * @param Request|null $request
     */
    public function testRestrictQueryBuilderByProductVisibility(Request $request = null)
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $params = ['some' => 'params'];
        $this->restrictQueryBuilderByProductVisibilityExpectations($qb, $params, $request);
        $this->productManager->restrictQueryBuilderByProductVisibility($qb, $params, $request);
        $this->productManager->setDataClass(self::DATA_CLASS);
    }

    /**
     * @return array
     */
    public function restrictQueryBuilderByProductVisibilityDataProvider()
    {
        return [
            'withoutRequestParam' => ['request' => null],
            'withRequestParam'    => ['request' => new Request()],
        ];
    }

    public function testCreateVisibleProductQueryBuilder()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        /** @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('createQueryBuilder')->with('product')->willReturn($qb);
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);
        $this->registry->expects($this->once())->method('getManagerForClass')->with(self::DATA_CLASS)->willReturn($em);
        $params = ['some' => 'params'];
        $request = new Request();
        $this->restrictQueryBuilderByProductVisibilityExpectations($qb, $params, $request);
        $this->assertEquals($qb, $this->productManager->createVisibleProductQueryBuilder($params, $request));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $dataParameters
     * @param Request|null $request
     */
    protected function restrictQueryBuilderByProductVisibilityExpectations(
        QueryBuilder $queryBuilder,
        array $dataParameters,
        Request $request = null
    ) {
        if (!$request) {
            $request = new Request();
            $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        } else {
            $this->requestStack->expects($this->never())->method('getCurrentRequest');
        }
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            ProductSelectDBQueryEvent::NAME,
            new ProductSelectDBQueryEvent($queryBuilder, new ParameterBag($dataParameters), $request)
        );
        $this->modifier->expects($this->once())->method('modifyByStatus')
            ->with($queryBuilder, [Product::STATUS_ENABLED]);
    }
}
