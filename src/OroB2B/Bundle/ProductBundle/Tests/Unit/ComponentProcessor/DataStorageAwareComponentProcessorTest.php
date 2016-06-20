<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataStorageAwareComponentProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage
     */
    protected $storage;

    /**
     * @var DataStorageAwareComponentProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorFilter
     */
    protected $componentProcessorFilter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->componentProcessorFilter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilterInterface')
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->processor = new DataStorageAwareComponentProcessor(
            $this->router,
            $this->storage,
            $this->securityFacade,
            $this->session,
            $this->translator
        );
        $this->processor->setComponentProcessorFilter($this->componentProcessorFilter);
    }

    protected function tearDown()
    {
        unset($this->router, $this->storage, $this->processor);
    }

    public function testProcessWithoutRedirectRoute()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => ['param' => 42]];
        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->will($this->returnArgument(0));

        $this->router->expects($this->never())
            ->method($this->anything());

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->assertNull($this->processor->process($data, new Request()));
    }

    public function testProcessWithRedirectRoute()
    {
        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->will($this->returnArgument(0));
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => ['param' => 42]];
        $redirectRouteName = 'redirect_route';
        $redirectUrl = '/redirect/url';
        $expectedResponseContent = (new RedirectResponse($redirectUrl))->getContent();

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $redirectRouteName,
                [ProductDataStorage::STORAGE_KEY => true],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($redirectUrl);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->processor->setRedirectRouteName($redirectRouteName);

        $this->assertEquals(
            $expectedResponseContent,
            $this->processor->process($data, new Request())->getContent()
        );
    }

    public function testProcessorName()
    {
        $name = 'test_name';

        $this->assertNull($this->processor->getName());

        $this->processor->setName($name);

        $this->assertEquals($name, $this->processor->getName());
    }

    /**
     * @param string $acl
     * @param bool $isGranted
     * @param bool $hasLoggedUser
     * @param bool $expected
     * @dataProvider processorIsAllowedProvider
     */
    public function testProcessorIsAllowed($acl, $isGranted, $hasLoggedUser, $expected)
    {
        if (null !== $acl) {
            $this->securityFacade->expects($this->any())
                ->method('hasLoggedUser')
                ->willReturn($hasLoggedUser);
            $this->securityFacade->expects($this->any())->method('isGranted')
                ->with($acl)->willReturn($isGranted);
        }

        $this->processor->setAcl($acl);
        $this->assertEquals($expected, $this->processor->isAllowed());
    }

    /**
     * @return array
     */
    public function processorIsAllowedProvider()
    {
        return [
            [null, true, false, true],
            ['fail', false, false, false],
            ['fail', true, false, false],
            ['fail', false, true, false],
            ['success', true, true, true],
        ];
    }

    public function testValidationRequired()
    {
        $this->assertTrue($this->processor->isValidationRequired());

        $this->processor->setValidationRequired(false);

        $this->assertFalse($this->processor->isValidationRequired());
    }

    /**
     * @dataProvider processorWithScopeDataProvider
     * @param string $scope
     * @param array $data
     * @param array $allowedData
     * @param string $errorMessageSkus
     * @param bool|true $isRedirectRoute
     */
    public function testProcessorWithScope(
        $scope,
        array $data,
        array $allowedData,
        $errorMessageSkus,
        $isRedirectRoute = false
    ) {
        $this->setupProcessorScope($scope, $data, $allowedData);

        $this->setupErrorMessages($errorMessageSkus);

        if ($isRedirectRoute) {
            $this->assertProcessorReturnRedirectResponse($this->processor, $this->router, $data);
        } else {
            $this->assertNull($this->processor->process($data, new Request()));
        }
    }

    /**
     * @return array
     */
    public function processorWithScopeDataProvider()
    {
        return [
            'restricted several with redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
                'isRedirectRoute' => true,
            ],
            'restricted one with redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
                'isRedirectRoute' => true,
            ],
            'restricted several without redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
            ],
            'restricted one without redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
            ],
        ];
    }

    /**
     * @dataProvider processorWithScopeAllRestricted
     * @param string $scope
     * @param array $data
     * @param string|null $errorMessageSkus
     * @param bool|false $isRedirectRoute
     */
    public function testProcessorWithScopeAllRestricted(
        $scope,
        array $data,
        $errorMessageSkus,
        $isRedirectRoute = false
    ) {
        $filteredData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => []];

        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->will($this->returnValue($filteredData));

        $this->setupProcessorScope($scope, $data, $filteredData);

        $this->setupErrorMessages($errorMessageSkus);

        if ($isRedirectRoute) {
            $this->processor->setRedirectRouteName('route');
        }
        $this->router->expects($this->never())
            ->method('generate');

        $this->assertNull($this->processor->process($data, new Request()));
    }

    /**
     * @return array
     */
    public function processorWithScopeAllRestricted()
    {
        return [
            'with redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'errorMessageSkus' => 'sku01, sku02, sku03',
                'isRedirectRoute' => true,
            ],
            'without redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'errorMessageSkus' => 'sku01, sku02, sku03',
            ],
        ];
    }

    /**
     * @dataProvider processorWithScopeAllAllowedDataProvider
     * @param string $scope
     * @param array $data
     * @param bool|false $isRedirectRoute
     */
    public function testProcessorWithScopeAllAllowed($scope, array $data, $isRedirectRoute = false)
    {
        $this->setupProcessorScope($scope, $data, $data);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->session->expects($this->never())
            ->method('getFlashBag');

        if ($isRedirectRoute) {
            $this->assertProcessorReturnRedirectResponse($this->processor, $this->router, $data);
        } else {
            $this->assertNull($this->processor->process($data, new Request()));
        }
    }

    /**
     * @return array
     */
    public function processorWithScopeAllAllowedDataProvider()
    {
        return [
            'with redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'isRedirectRoute' => true,
            ],
            'without redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $scope
     * @param array $data
     * @param array $allowedData
     */
    protected function setupProcessorScope($scope, $data, $allowedData)
    {
        $this->componentProcessorFilter->expects($this->once())
            ->method('filterData')
            ->with($data, ['scope' => $scope])
            ->willReturn($allowedData);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($allowedData);

        $this->processor->setScope($scope);
    }

    /**
     * @param string $errorMessageSkus
     */
    protected function setupErrorMessages($errorMessageSkus)
    {
        $this->translator->expects($this->any())
            ->method('transChoice')
            ->with(
                'orob2b.product.frontend.quick_add.messages.not_added_products',
                count(explode(', ', $errorMessageSkus)),
                ['%sku%' => $errorMessageSkus]
            );

        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warning');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
    }

    /**
     * @param DataStorageAwareComponentProcessor $processor
     * @param \PHPUnit_Framework_MockObject_MockObject $routerMock
     * @param array $data
     * @param string $targetUrl
     */
    protected function assertProcessorReturnRedirectResponse($processor, $routerMock, $data, $targetUrl = 'url')
    {
        $redirectRoute = 'route';

        $processor->setRedirectRouteName($redirectRoute);

        $routerMock->expects($this->once())
            ->method('generate')
            ->with($redirectRoute, [ProductDataStorage::STORAGE_KEY => true])
            ->willReturn($targetUrl);

        $response = $this->processor->process($data, new Request());

        $this->assertNotNull($response);
        $this->assertEquals($targetUrl, $response->getTargetUrl());
    }
}
