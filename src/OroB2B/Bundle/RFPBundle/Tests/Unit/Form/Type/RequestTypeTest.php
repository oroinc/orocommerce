<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductCollectionType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusSelectType;

class RequestTypeTest extends AbstractTest
{
    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new RequestType();
        $this->formType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\Request');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request',
                    'intention'  => 'rfp_request',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, 'comment', [$requestProductItem]);

        $longStr    = str_repeat('a', 256);
        $longEmail  = $longStr . '@example.com';
        $email      = 'test@example.com';

        return [
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName'     => 'FirstName',
                    'lastName'      => 'LastName',
                    'email'         => $email,
                    'body'          => 'body',
                    'role'          => 'role',
                    'phone'         => '123',
                    'company'       => 'company',
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
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role', '123')
                    ->addRequestProduct($requestProduct),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role', '123'),
            ],
            'valid data empty items' => [
                'isValid'       => true,
                'submittedData' => [
                    'firstName'     => 'FirstName',
                    'lastName'      => 'LastName',
                    'email'         => $email,
                    'body'          => 'body',
                    'role'          => 'role',
                    'phone'         => '123',
                    'company'       => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role', '123'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role', '123'),
            ],
            'empty first name' => [
                'isValid'       => false,
                'submittedData' => [
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest(null, 'LastName', $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'first name len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => $longStr,
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest($longStr, 'LastName', $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty last name' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', null, $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'last name len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => $longStr,
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', $longStr, $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty email' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', null, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'invalid email' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => 'no-email',
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', 'no-email', 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'email len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $longEmail,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $longEmail, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'empty body' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'role'      => 'role',
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, null, 'company', 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'company len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => 'role',
                    'company'   => $longStr,
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', $longStr, 'role'),
                'defaultData'   => $this->getRequest(),
            ],
            'role len > 255' => [
                'isValid'       => false,
                'submittedData' => [
                    'firstName' => 'FirstName',
                    'lastName'  => 'LastName',
                    'email'     => $email,
                    'body'      => 'body',
                    'role'      => $longStr,
                    'company'   => 'company',
                ],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', $longStr),
                'defaultData'   => $this->getRequest(),
            ],
        ];
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $body
     * @param string $company
     * @param string $role
     * @param string $phone
     * @return Request
     */
    protected function getRequest(
        $firstName = null,
        $lastName = null,
        $email = null,
        $body = null,
        $company = null,
        $role = null,
        $phone = null
    ) {
        $request = new Request();

        $request
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setBody($body)
            ->setCompany($company)
            ->setRole($role)
            ->setPhone($phone)
        ;

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        $accountSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
            ],
            AccountSelectType::NAME
        );

        $accountUserSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', 2),
            ],
            AccountUserSelectType::NAME
        );

        $requestStatusSelectType = new StubEntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', 1),
            ],
            RequestStatusSelectType::NAME
        );

        $requestProductType = new RequestProductType($productUnitLabelFormatter);
        $requestProductType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductCollectionType::NAME      => new RequestProductCollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    ProductRemovedSelectType::NAME          => new StubProductRemovedSelectType(),
                    ProductUnitRemovedSelectionType::NAME   => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $accountSelectType->getName()           => $accountSelectType,
                    $requestProductType->getName()          => $requestProductType,
                    $accountUserSelectType->getName()       => $accountUserSelectType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $requestProductItemType->getName()      => $requestProductItemType,
                    $requestStatusSelectType->getName()     => $requestStatusSelectType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
