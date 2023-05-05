<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Tests\Unit\Manager\AbstractAddressManagerTest;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;

class QuoteAddressManagerTest extends AbstractAddressManagerTest
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|QuoteAddressProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    /** @var QuoteAddressManager */
    protected $manager;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(QuoteAddressProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->manager = new QuoteAddressManager(
            $this->provider,
            $this->registry,
            QuoteAddress::class
        );
    }

    /**
     * @dataProvider quoteDataProvider
     */
    public function testUpdateFromAbstract(
        AbstractAddress $address,
        QuoteAddress $expected = null,
        AbstractAddress $expectedCustomerAddress = null,
        AbstractAddress $expectedCustomerUserAddress = null,
        QuoteAddress $quoteAddress = null
    ) {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['country', 'region']);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->isType('string'))
            ->willReturn($em);

        $quoteAddress = $this->manager->updateFromAbstract($address, $quoteAddress);
        $this->assertEquals($expected, $quoteAddress);
        $this->assertEquals($expectedCustomerAddress, $quoteAddress->getCustomerAddress());
        $this->assertEquals($expectedCustomerUserAddress, $quoteAddress->getCustomerUserAddress());
    }

    public function quoteDataProvider(): array
    {
        $country = new Country('US');
        $region = new Region('US-AL');

        return [
            'empty customer address' => [
                $customerAddress = new CustomerAddress(),
                (new QuoteAddress())
                    ->setCustomerAddress($customerAddress),
                $customerAddress
            ],
            'empty customer user address' => [
                $customerUserAddress = new CustomerUserAddress(),
                (new QuoteAddress())
                    ->setCustomerUserAddress($customerUserAddress),
                null,
                $customerUserAddress
            ],
            'from customer address' => [
                $customerAddress = (new CustomerAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setCustomerAddress($customerAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                $customerAddress
            ],
            'from customer user address' => [
                $customerUserAddress = (new CustomerUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setCustomerUserAddress($customerUserAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $customerUserAddress
            ],
            'do not override value from existing with empty one' => [
                $customerUserAddress = (new CustomerUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new QuoteAddress())
                    ->setCustomerUserAddress($customerUserAddress)
                    ->setLabel('ExistingLabel')
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $customerUserAddress,
                (new QuoteAddress())
                    ->setLabel('ExistingLabel')
            ],
        ];
    }

    /**
     * @dataProvider groupedAddressDataProvider
     */
    public function testGetGroupedAddresses(
        Quote $quote,
        array $customerAddresses = [],
        array $customerUserAddresses = [],
        array $expected = []
    ) {
        $this->provider->expects($this->any())
            ->method('getCustomerAddresses')
            ->willReturn($customerAddresses);
        $this->provider->expects($this->any())
            ->method('getCustomerUserAddresses')
            ->willReturn($customerUserAddresses);

        $this->manager->addEntity('au', CustomerUserAddress::class);
        $this->manager->addEntity('a', CustomerAddress::class);

        $result = $this->manager->getGroupedAddresses($quote, AddressType::TYPE_BILLING, 'oro.sale.quote.');

        $this->assertInstanceOf(TypedOrderAddressCollection::class, $result);
        $this->assertEquals($expected, $result->toArray());
    }

    public function groupedAddressDataProvider(): array
    {
        return [
            'empty customer user' => [new Quote()],
            'empty customer' => [
                (new Quote())->setCustomerUser(new CustomerUser()),
                [],
                [$this->getCustomerUserAddress(1), $this->getCustomerUserAddress(2)],
                [
                    'oro.sale.quote.form.address.group_label.customer_user' => [
                        'au_1' => $this->getCustomerUserAddress(1),
                        'au_2' => $this->getCustomerUserAddress(2)
                    ],
                ],
            ],
            'customer' => [
                (new Quote())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [$this->getCustomerAddress(1), $this->getCustomerAddress(2)],
                [],
                [
                    'oro.sale.quote.form.address.group_label.customer' => [
                        'a_1' => $this->getCustomerAddress(1),
                        'a_2' => $this->getCustomerAddress(2)
                    ],
                ],
            ],
            'full' => [
                (new Quote())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [$this->getCustomerAddress(1), $this->getCustomerAddress(2)],
                [$this->getCustomerUserAddress(1), $this->getCustomerUserAddress(2)],
                [
                    'oro.sale.quote.form.address.group_label.customer' => [
                        'a_1' => $this->getCustomerAddress(1),
                        'a_2' => $this->getCustomerAddress(2)
                    ],
                    'oro.sale.quote.form.address.group_label.customer_user' => [
                        'au_1' => $this->getCustomerUserAddress(1),
                        'au_2' => $this->getCustomerUserAddress(2)
                    ],
                ],
            ],
        ];
    }
}
