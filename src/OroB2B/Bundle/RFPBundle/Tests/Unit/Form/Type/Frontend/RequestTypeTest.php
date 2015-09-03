<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceListAwareSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestProductCollectionType;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;
use OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;
use OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type\Stub\StubProductPriceListAwareSelectType;

class RequestTypeTest extends AbstractTest
{
    const DATA_CLASS = 'OroB2B\Bundle\RFPBundle\Entity\Request';
    const REQUEST_STATUS_CLASS = 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus';

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
        $requestStatus->setName(RequestStatus::DRAFT);

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects(static::any())
            ->method('findOneBy')
            ->with(['name' => RequestStatus::DRAFT])
            ->willReturn($requestStatus);

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects(static::any())
            ->method('getRepository')
            ->with(self::REQUEST_STATUS_CLASS)
            ->willReturn($repository);

        /**
         * @var \Doctrine\Common\Persistence\ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry
         */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects(static::any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($manager);

        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects(static::any())
            ->method('getManagerForClass')
            ->with(self::REQUEST_STATUS_CLASS)
            ->willReturn($manager);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator
         */
        $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');

        $validator->expects(static::any())
            ->method('validate')
            ->will(static::returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

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
        /**
         * @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver
         */
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
        static::assertEquals(RequestStatus::DRAFT, $request->getStatus()->getName());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, 'comment', [$requestProductItem]);

        $email      = 'test@example.com';

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
                    'body'      => 'body',
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
                    ->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role', '+38 (044) 247-68-00')
                    ->addRequestProduct($requestProduct)->setStatus(
                        (new RequestStatus())->setName(RequestStatus::DRAFT)
                    ),
                'defaultData'   => $this->getRequest(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $requestProductType         = new RequestProductType();
        $requestProductType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductCollectionType::NAME      => new RequestProductCollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    ProductPriceListAwareSelectType::NAME   => new StubProductPriceListAwareSelectType(),
                    ProductUnitRemovedSelectionType::NAME   => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $requestProductType->getName()          => $requestProductType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $requestProductItemType->getName()      => $requestProductItemType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
