<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountTypedAddressType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AddressTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\CustomerTypedAddressWithDefaultTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityType;

class CustomerTypedAddressTypeTest extends FormIntegrationTestCase
{
    /** @var AccountTypedAddressType */
    protected $formType;

    /** @var AddressType */
    protected $billingType;

    /** @var AddressType */
    protected $shippingType;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->billingType = new AddressType(AddressType::TYPE_BILLING);
        $this->shippingType = new AddressType(AddressType::TYPE_SHIPPING);

        $this->em = $this->createEntityManagerMock();
        $this->addressRepository = $this->createRepositoryMock([
            $this->billingType,
            $this->shippingType
        ]);
        $this->addressRepository->expects($this->any())
            ->method('findBy')
            ->will($this->returnCallback(function ($params) {
                $result = [];
                foreach ($params['name'] as $name) {
                    switch ($name) {
                        case AddressType::TYPE_BILLING:
                            $result[] = $this->billingType;
                            break;
                        case AddressType::TYPE_SHIPPING:
                            $result[] = $this->shippingType;
                            break;
                    }
                }

                return $result;
            }));
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountTypedAddressType();
        $this->formType->setAddressTypeDataClass('Oro\Bundle\AddressBundle\Entity\AddressType');
        $this->formType->setDataClass('OroB2B\Bundle\AccountBundle\Entity\AccountAddress');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $addressType = new EntityType(
            [
                AddressType::TYPE_BILLING => $this->billingType,
                AddressType::TYPE_SHIPPING => $this->shippingType,
            ],
            'translatable_entity'
        );

        $addressTypeStub = new AddressTypeStub();

        return [
            new PreloadedExtension(
                [
                    $addressType->getName() => $addressType,
                    CustomerTypedAddressWithDefaultTypeStub::NAME  => new CustomerTypedAddressWithDefaultTypeStub([
                        $this->billingType,
                        $this->shippingType
                    ], $this->em),
                    $addressTypeStub->getName()  => $addressTypeStub,
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param null  $updateOwner
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        $submittedData,
        $expectedData,
        $updateOwner = null
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        if (is_object($expectedData) && $updateOwner) {
            $expectedData->setOwner($updateOwner);
        }
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        $customerAddressWithAllDefaultTypes = new AccountAddress();
        $customerAddressWithAllDefaultTypes
            ->setPrimary(true)
            ->setTypes(new ArrayCollection([$this->billingType, $this->shippingType]))
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]));

        return [
            'all default types' => [
                'options' => ['single_form' => false],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'types' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING],
                    'defaults' => ['default' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING]],
                    'primary' => true,
                ],
                'expectedData' => $customerAddressWithAllDefaultTypes,
            ],
        ];
    }

    /**
     * @dataProvider submitWithFormSubscribersProvider
     * @param array $options
     * @param       $defaultData
     * @param       $viewData
     * @param       $submittedData
     * @param       $expectedData
     * @param       $otherAddresses
     * @param null  $updateOwner
     */
    public function testSubmitWithSubscribers(
        array $options,
        $defaultData,
        $viewData,
        $submittedData,
        $expectedData,
        $otherAddresses,
        $updateOwner = null
    ) {
        $this->testSubmit($options, $defaultData, $viewData, $submittedData, $expectedData, $updateOwner);

        /** @var AccountAddress $otherAddress */
        foreach ($otherAddresses as $otherAddress) {
            /** @var AddressType $otherDefaultType */
            foreach ($otherAddress->getDefaults() as $otherDefaultType) {
                $this->assertNotContains($otherDefaultType->getName(), $submittedData['defaults']['default']);
            }
        }
    }

    /**
     * @return array
     */
    public function submitWithFormSubscribersProvider()
    {
        $customerAddress1 = new AccountAddress();
        $customerAddress1
            ->setTypes(new ArrayCollection([$this->billingType, $this->shippingType]));

        $customerAddress2 = new AccountAddress();
        $customerAddress2
            ->setTypes(new ArrayCollection([$this->billingType, $this->shippingType]))
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]));

        $customerAddressExpected = new AccountAddress();
        $customerAddressExpected
            ->setPrimary(true)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->removeType($this->billingType) // emulate working of forms. It first delete types and after add it
            ->removeType($this->shippingType)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]));

        $customer = new Account();
        $customer->addAddress($customerAddress1);
        $customer->addAddress($customerAddress2);

        return [
            'FixCustomerAddressesDefaultSubscriber check' => [
                'options' => [],
                'defaultData' => $customerAddress1,
                'viewData' => $customerAddress1,
                'submittedData' => [
                    'types' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING],
                    'defaults' => ['default' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING]],
                    'primary' => true,
                ],
                'expectedData' => $customerAddressExpected,
                'otherAddresses' => [$customerAddress2],
                'updateOwner' => $customer
            ]
        ];

    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_account_typed_address', $this->formType->getName());
    }

    /**
     * @param array $entityModels
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRepositoryMock($entityModels = [])
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue($entityModels));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        return $repo;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
