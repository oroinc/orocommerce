<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
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

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->initClient();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
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
            ->with('visibility_data')
            ->will($this->returnValue($visibilityData));

        /** @var RequestStack $requestStack */
        $requestStack = $this->getRequestStack($request);

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $requestStack,
            $this->eventDispatcher
        );

        $searchHandler->setAclHelper($this->getContainer()->get('oro_security.acl_helper'));

        if (!empty($visibilityData)) {
            $this->eventDispatcher->expects($this->once())
                ->method('dispatch')
                ->with(ProductSelectDBQueryEvent::NAME);
        } else {
            $this->eventDispatcher->expects($this->never())
                ->method('dispatch');
        }

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
                    'scope' => 'rfp'
                ]
            ],
            'without visibility data' => [
                'visibilityData' => []
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
            $this->eventDispatcher
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
