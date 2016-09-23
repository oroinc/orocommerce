<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\AccountBundle\Form\Type\Frontend\AccountUserMultiSelectType;

use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType as FrontendRequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemCollectionType;
use Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;

class RequestTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'Oro\Bundle\RFPBundle\Entity\Request';
    const REQUEST_STATUS_CLASS = 'Oro\Bundle\RFPBundle\Entity\RequestStatus';

    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $requestStatus = new RequestStatus();
        $requestStatus->setName(RequestStatus::OPEN);

        /* @var $repository ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects(static::any())
            ->method('findOneBy')
            ->with(['name' => RequestStatus::OPEN])
            ->willReturn($requestStatus);

        /* @var $manager ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects(static::any())
            ->method('getRepository')
            ->with(self::REQUEST_STATUS_CLASS)
            ->willReturn($repository);

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects(static::any())
            ->method('getManagerForClass')
            ->with(self::REQUEST_STATUS_CLASS)
            ->willReturn($manager);

        /* @var $configManager ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects(static::any())
            ->method('get')
            ->with('oro_rfp.default_request_status')
            ->willReturn(RequestStatus::OPEN);

        $this->formType = new RequestType($configManager, $registry);
        $this->formType->setDataClass(self::DATA_CLASS);
        $this->formType->setRequestStatusClass(self::REQUEST_STATUS_CLASS);

        parent::setUp();
    }

    /**
     * Test configureOptions
     */
    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');

        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        static::assertEquals(RequestType::NAME, $this->formType->getName());
    }

    /**
     * Test postSubmit
     */
    public function testPostSubmit()
    {
        $request = new Request();
        $form = $this->factory->create($this->formType, $request);

        static::assertEmpty($request->getStatus());

        $this->formType->postSubmit(new FormEvent($form, $request));

        static::assertNotNull($request->getStatus());
        static::assertEquals(RequestStatus::OPEN, $request->getStatus()->getName());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, 'comment', [$requestProductItem]);

        $email      = 'test@example.com';
        $date       = '2015-10-15';
        $dateObj    = new \DateTime($date . 'T00:00:00+0000');

        return [
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'phone'     => '+38 (044) 247-68-00',
                    'company'   => 'company',
                    'role'      => 'role',
                    'note'      => 'note',
                    'poNumber'  => 'poNumber',
                    'shipUntil' => $date,
                    'requestProducts' => [
                        [
                            'product'   => 2,
                            'comment'   => 'comment',
                            'requestProductItems' => [
                                [
                                    'quantity' => 10,
                                    'productUnit' => 'kg',
                                    'price' => ['value' => 20, 'currency' => 'USD',],
                                ],
                            ],
                        ],
                    ],
                    'assignedAccountUsers' => [10],
                ],
                'expectedData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        'poNumber',
                        $dateObj
                    )
                    ->addRequestProduct($requestProduct)->setStatus(
                        (new RequestStatus())->setName(RequestStatus::OPEN)
                    )
                    ->addAssignedAccountUser($this->getAccountUser(10)),
                'defaultData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        'poNumber',
                        $dateObj
                    )
                    ->addRequestProduct($requestProduct)->setStatus(
                        (new RequestStatus())->setName(RequestStatus::OPEN)
                    ),
            ],
            'empty PO number' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'phone'     => '+38 (044) 247-68-00',
                    'company'   => 'company',
                    'role'      => 'role',
                    'note'      => 'note',
                    'poNumber'  => null,
                    'shipUntil' => null,
                    'requestProducts' => [
                        [
                            'product'   => 2,
                            'comment'   => 'comment',
                            'requestProductItems' => [
                                [
                                    'quantity' => 10,
                                    'productUnit' => 'kg',
                                    'price' => ['value' => 20, 'currency' => 'USD',],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        null,
                        null
                    )
                    ->addRequestProduct($requestProduct)->setStatus(
                        (new RequestStatus())->setName(RequestStatus::OPEN)
                    ),
                'defaultData'  => $this
                    ->getRequest(
                        'FirstName',
                        'LastName',
                        $email,
                        'note',
                        'company',
                        'role',
                        '+38 (044) 247-68-00',
                        null,
                        null
                    )
                    ->addRequestProduct($requestProduct)->setStatus(
                        (new RequestStatus())->setName(RequestStatus::OPEN)
                    ),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $accountUserMultiSelectType = $this->prepareAccountUserMultiSelectType();
        $requestProductType         = new RequestProductType($productUnitLabelFormatter);
        $requestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');
        $frontendRequestProductType = new FrontendRequestProductType();
        $frontendRequestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductCollectionType::NAME      => new RequestProductCollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    ProductUnitSelectionType::NAME          => new ProductUnitSelectionTypeStub(),
                    ProductSelectType::NAME                 => new ProductSelectTypeStub(),
                    OroDateType::NAME                       => new OroDateType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $requestProductType->getName()          => $requestProductType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $requestProductItemType->getName()      => $requestProductItemType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                    $accountUserMultiSelectType->getName()  => $accountUserMultiSelectType,
                    $frontendRequestProductType->getName()  => $frontendRequestProductType,
                    QuantityTypeTrait::$name                => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @return EntityType
     */
    protected function prepareAccountUserMultiSelectType()
    {
        return new EntityType(
            [
                10 => $this->getAccountUser(10),
                11 => $this->getAccountUser(11),
            ],
            AccountUserMultiSelectType::NAME,
            [
                'multiple' => true
            ]
        );
    }
}
