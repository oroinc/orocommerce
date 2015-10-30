<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends WebTestCase
{
    const TEST_ENTITY_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /** @var array */
    protected $testProperties = ['sku'];

    /** @var ProductVisibilityLimitedSearchHandler */
    protected $searchHandler;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $productManager;

    protected function setUp()
    {
        $this->initClient();

        $this->productManager = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider searchDataProvider
     * @param array $visibilityData
     */
    public function testSearch(array $visibilityData)
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
            ->method('get')
            ->with(ProductSelectType::DATA_PARAMETERS)
            ->will($this->returnValue($visibilityData));

        /** @var RequestStack $requestStack */
        $requestStack = $this->getRequestStack($request);

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $requestStack,
            $this->productManager
        );

        $searchHandler->setAclHelper($this->getContainer()->get('oro_security.acl_helper'));

        $this->productManager->expects($this->once())->method('restrictQueryBuilderByProductVisibility')->with(
            $this->isInstanceOf('Doctrine\ORM\QueryBuilder'),
            $visibilityData,
            $request
        );

        $searchHandler->initDoctrinePropertiesByManagerRegistry($this->getContainer()->get('doctrine'));
        $searchHandler->search('test', 1, 10);
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            'with visibility data' => [
                'visibilityData' => [
                    'scope' => 'rfp',
                ],
            ],
            'without visibility data' => [
                'visibilityData' => [],
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Search handler is not fully configured
     */
    public function testCheckAllDependenciesInjectedException()
    {
        $requestStack = new RequestStack();

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $requestStack,
            $this->productManager
        );
        $searchHandler->search('test', 1, 10);
    }

    /**
     * @param Request $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack(Request $request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
