<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserTypedAddressType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AccountTypedAddressWithDefaultTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\FrontendAccountUserSelectTypeStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendAccountUserTypedAddressTypeTest extends AccountTypedAddressTypeTest
{
    /** @var FrontendAccountUserTypedAddressType */
    protected $formType;

    /** @var  AclHelper */
    protected $aclHelper;
    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->aclHelper = $this->createAclHelperMock();
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

        return [
            new PreloadedExtension(
                [
                    $addressType->getName() => $addressType,
                    AccountTypedAddressWithDefaultTypeStub::NAME  => new AccountTypedAddressWithDefaultTypeStub([
                        $this->billingType,
                        $this->shippingType
                    ], $this->em),
                    FrontendAccountUserSelectTypeStub::NAME => new FrontendAccountUserSelectTypeStub($this->aclHelper),
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
