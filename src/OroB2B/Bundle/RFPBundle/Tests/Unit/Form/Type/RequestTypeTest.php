<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductCollectionType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
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

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request',
                    'intention'  => 'rfp_request',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestType::NAME, $this->formType->getName());
    }

    /**
     * @return array
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
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role')
                    ->addRequestProduct($requestProduct),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role'),
            ],
            'valid data empty items' => [
                'isValid'       => true,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', 'role'),
            ],
            'empty first name' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest(null, 'LastName', $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest(null, 'LastName', $email, 'body', 'company', 'role'),
            ],
            'first name len > 255' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest($longStr, 'LastName', $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest($longStr, 'LastName', $email, 'body', 'company', 'role'),
            ],
            'empty last name' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', null, $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', null, $email, 'body', 'company', 'role'),
            ],
            'last name len > 255' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', $longStr, $email, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', $longStr, $email, 'body', 'company', 'role'),
            ],
            'empty email' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', null, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', null, 'body', 'company', 'role'),
            ],
            'invalid email' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', 'no-email', 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', 'no-email', 'body', 'company', 'role'),
            ],
            'email len > 255' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'Last Name', $longEmail, 'body', 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'Last Name', $longEmail, 'body', 'company', 'role'),
            ],
            'empty body' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, null, 'company', 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, null, 'company', 'role'),
            ],
            'company len > 255' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', $longStr, 'role'),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', $longStr, 'role'),
            ],
            'role len > 255' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', $longStr),
                'defaultData'   => $this->getRequest('FirstName', 'LastName', $email, 'body', 'company', $longStr),
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
     * @return Request
     */
    protected function getRequest(
        $firstName = null,
        $lastName = null,
        $email = null,
        $body = null,
        $company = null,
        $role = null
    ) {
        $request = new Request();

        $request
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setBody($body)
            ->setCompany($company)
            ->setRole($role)
        ;

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $productRemovedSelectType   = new StubProductRemovedSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType($translator);
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        $requestProductType = new RequestProductType($translator);
        $requestProductType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductCollectionType::NAME      => new RequestProductCollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $productRemovedSelectType->getName()    => $productRemovedSelectType,
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
