<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Form\DataTransformer\AddressTypeDefaultTransformer;

class AddressTypeDefaultTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager  */
    protected $em;

    /** @var EntityRepository  */
    protected $addressRepository;

    /** @var AddressTypeDefaultTransformer */
    protected $transformer;

    /** @var AddressType */
    protected $billingAddressType;

    /** @var AddressType */
    protected $shippingAddressType;

    /**
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->billingAddressType = new AddressType(AddressType::TYPE_BILLING);
        $this->shippingAddressType = new AddressType(AddressType::TYPE_SHIPPING);

        $this->em = $this->createEntityManagerMock();
        $this->addressRepository = $this->createRepositoryMock([
            $this->billingAddressType,
            $this->shippingAddressType
        ]);
        $this->addressRepository->expects($this->any())
            ->method('findBy')
            ->will($this->returnCallback(function ($params) {
                $result = [];
                foreach ($params['name'] as $name) {
                    switch ($name) {
                        case AddressType::TYPE_BILLING:
                            $result[] = $this->billingAddressType;
                            break;
                        case AddressType::TYPE_SHIPPING:
                            $result[] = $this->shippingAddressType;
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
        $this->transformer = new AddressTypeDefaultTransformer($this->em);
    }

    /**
     * @param $parameters
     * @param $expected
     * @dataProvider transformerProvider
     */
    public function testTransform($parameters, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($parameters));
    }

    /**
     * @return array
     */
    public function transformerProvider()
    {
        return [
            'nullable params' => [
                'parameters' => null,
                'expected' => []
            ],
            'default' => [
                'parameters' => [$this->shippingAddressType, $this->billingAddressType],
                'expected' => ['default' => [AddressType::TYPE_SHIPPING, AddressType::TYPE_BILLING]]
            ],
        ];
    }

    /**
     * @param $parameters
     * @param $expected
     * @dataProvider reverseTransformerProvider
     */
    public function testReverseTransform($parameters, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($parameters));
    }

    /**
     * @return array
     */
    public function reverseTransformerProvider()
    {
        return [
            'nullable params' => [
                'parameters' => ['default' => null],
                'expected' => []
            ],
            'empty params' => [
                'parameters' => [],
                'expected' => []
            ],
            'default' => [
                'parameters' => ['default' => [AddressType::TYPE_SHIPPING, AddressType::TYPE_BILLING]],
                'expected' => [$this->shippingAddressType, $this->billingAddressType]
            ],
        ];
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
