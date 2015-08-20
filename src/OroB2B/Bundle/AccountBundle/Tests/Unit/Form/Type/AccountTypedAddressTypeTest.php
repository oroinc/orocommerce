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
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AccountTypedAddressWithDefaultTypeStub;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityType;

class AccountTypedAddressTypeTest extends FormIntegrationTestCase
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
                    AccountTypedAddressWithDefaultTypeStub::NAME  => new AccountTypedAddressWithDefaultTypeStub([
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
            $expectedData->setFrontendOwner($updateOwner);
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
        $accountAddressWithAllDefaultTypes = new AccountAddress();
        $accountAddressWithAllDefaultTypes
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
                'expectedData' => $accountAddressWithAllDefaultTypes,
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
        $accountAddress1 = new AccountAddress();
        $accountAddress1
            ->setTypes(new ArrayCollection([$this->billingType, $this->shippingType]));

        $accountAddress2 = new AccountAddress();
        $accountAddress2
            ->setTypes(new ArrayCollection([$this->billingType, $this->shippingType]))
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]));

        $accountAddressExpected = new AccountAddress();
        $accountAddressExpected
            ->setPrimary(true)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->removeType($this->billingType) // emulate working of forms. It first delete types and after add it
            ->removeType($this->shippingType)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]));

        $account = new Account();
        $account->addAddress($accountAddress1);
        $account->addAddress($accountAddress2);

        return [
            'FixAccountAddressesDefaultSubscriber check' => [
                'options' => [],
                'defaultData' => $accountAddress1,
                'viewData' => $accountAddress1,
                'submittedData' => [
                    'types' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING],
                    'defaults' => ['default' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING]],
                    'primary' => true,
                ],
                'expectedData' => $accountAddressExpected,
                'otherAddresses' => [$accountAddress2],
                'updateOwner' => $account
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
