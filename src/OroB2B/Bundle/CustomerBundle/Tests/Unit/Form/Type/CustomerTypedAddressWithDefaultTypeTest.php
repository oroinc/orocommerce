<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressWithDefaultType;

class CustomerTypedAddressWithDefaultTypeTest extends FormIntegrationTestCase
{
    /** @var CustomerTypedAddressWithDefaultType */
    protected $formType;

    /** @var AddressType */
    protected $billingType;

    /** @var AddressType */
    protected $shippingType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->billingType  = new AddressType(AddressType::TYPE_BILLING);
        $this->shippingType = new AddressType(AddressType::TYPE_SHIPPING);

        $this->addressRepository = $this->createRepositoryMock([
            $this->billingType,
            $this->shippingType
        ]);

        $this->em       = $this->createEntityManagerMock($this->addressRepository);
        $this->registry = $this->createManagerRegistryMock($this->em);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CustomerTypedAddressWithDefaultType();
        $this->formType->setRegistry($this->registry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        return [
            'without defaults' => [
                'options'       => ['class' => 'Oro\Bundle\AddressBundle\Entity\AddressType'],
                'defaultData'   => [],
                'viewData'      => [],
                'submittedData' => [
                    'default' => [],
                ],
                'expectedData'  => [],
            ],
            'all default types' => [
                'options'       => ['class' => 'Oro\Bundle\AddressBundle\Entity\AddressType'],
                'defaultData'   => [],
                'viewData'      => [],
                'submittedData' => [
                    'default' => [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING],
                ],
                'expectedData'  => [$this->billingType, $this->shippingType],
            ],
            'one default type' => [
                'options'       => ['class' => 'Oro\Bundle\AddressBundle\Entity\AddressType'],
                'defaultData'   => [],
                'viewData'      => [],
                'submittedData' => [
                    'default' => [AddressType::TYPE_SHIPPING],
                ],
                'expectedData'  => [$this->shippingType],
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_customer_typed_address_with_default', $this->formType->getName());
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

        $repo->expects($this->any())
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

        return $repo;
    }

    /**
     * @param $repo
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManagerMock($repo)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->createClassMetadataMock()));

        return $em;
    }

    /**
     * @param $em
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createManagerRegistryMock($em)
    {
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));
        $registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        return $registry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createClassMetadataMock()
    {
        $classMetadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('name'));

        $classMetadata->expects($this->any())
            ->method('getReflectionProperty')
            ->will($this->returnCallback(function ($field) {
                return $this->createReflectionProperty($field);
            }));

        return $classMetadata;
    }

    /**
     * @param $field
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createReflectionProperty($field)
    {
        $class = $this->getMockBuilder('\ReflectionProperty')
            ->disableOriginalConstructor()
            ->getMock();
        $class->expects($this->any())
            ->method('getValue')
            ->will($this->returnCallback(function ($entity) use ($field) {
                $method = 'get' . ucfirst($field);
                return $entity->$method();
            }));

        return $class;
    }
}
