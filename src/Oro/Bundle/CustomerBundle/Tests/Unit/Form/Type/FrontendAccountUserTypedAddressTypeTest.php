<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserSelectType;
use Symfony\Component\Form\PreloadedExtension;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserTypedAddressType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AccountTypedAddressWithDefaultTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class FrontendAccountUserTypedAddressTypeTest extends AccountTypedAddressTypeTest
{
    /** @var FrontendAccountUserTypedAddressType */
    protected $formType;

    /** @var  AclHelper */
    protected $aclHelper;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->aclHelper = $this->createAclHelperMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendAccountUserTypedAddressType();
        $this->formType->setAddressTypeDataClass('Oro\Bundle\AddressBundle\Entity\AddressType');
        $this->formType->setDataClass('Oro\Bundle\CustomerBundle\Entity\AccountAddress');
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

        $criteria = new Criteria();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $accountUserRepository =
            $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->getMock();

        $accountUserRepository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->with('account_user')
            ->willReturn($queryBuilder);

        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroCustomerBundle:AccountUser')
            ->willReturn($accountUserRepository);

        $this->aclHelper
            ->expects($this->any())
            ->method('applyAclToCriteria')
            ->with(AccountUser::class, $criteria, 'VIEW', ['account' => 'account_user.account'])
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects($this->any())
            ->method('addCriteria')
            ->with($criteria);

        return [
            new PreloadedExtension(
                [
                    $addressType->getName() => $addressType,
                    AccountTypedAddressWithDefaultTypeStub::NAME  => new AccountTypedAddressWithDefaultTypeStub([
                        $this->billingType,
                        $this->shippingType
                    ], $this->em),
                    FrontendAccountUserSelectType::NAME => new FrontendAccountUserSelectType(
                        $this->aclHelper,
                        $this->registry
                    ),
                    $addressTypeStub->getName()  => $addressTypeStub,
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
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
        $this->assertTrue($form->has('frontendOwner'));
        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
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

        $accountUser = new AccountUser();
        $accountUser->addAddress($accountAddress1);
        $accountUser->addAddress($accountAddress2);

        $accountAddressExpected = new AccountAddress();
        $accountAddressExpected
            ->setPrimary(true)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->removeType($this->billingType) // emulate working of forms. It first delete types and after add it
            ->removeType($this->shippingType)
            ->addType($this->billingType)
            ->addType($this->shippingType)
            ->setDefaults(new ArrayCollection([$this->billingType, $this->shippingType]))
            ->setFrontendOwner($accountUser);

        return [
            'FixAccountAddressesDefaultSubscriber check' => [
                'options' => [],
                'defaultData' => $accountAddress1,
                'viewData' => $accountAddress1,
                'submittedData' => [
                    'types' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING],
                    'defaults' => ['default' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING]],
                    'primary' => true,
                    'frontendOwner' => $accountUser
                ],
                'expectedData' => $accountAddressExpected,
                'otherAddresses' => [$accountAddress2],
                'updateOwner' => $accountUser
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('oro_account_frontend_account_user_typed_address', $this->formType->getName());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAclHelperMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
