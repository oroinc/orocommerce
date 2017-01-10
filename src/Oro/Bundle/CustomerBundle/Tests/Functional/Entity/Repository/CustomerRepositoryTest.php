<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;

/**
 * @dbIsolation
 */
class CustomerRepositoryTest extends WebTestCase
{
    /**
     * @var CustomerRepository
     */
    protected $repository;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:Customer');

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            ]
        );

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    /**
     * @dataProvider customerReferencesDataProvider
     * @param string $referenceName
     * @param array $expectedReferences
     * @param bool $withAclCheck
     */
    public function testGetChildrenIds($referenceName, array $expectedReferences, $withAclCheck = true)
    {
        /** @var Customer $customer */
        $customer = $this->getReference($referenceName);

        $expected = [];
        foreach ($expectedReferences as $reference) {
            $expected[] = $this->getReference($reference)->getId();
        }
        $childrenIds = $this->repository->getChildrenIds($customer->getId(), $withAclCheck ? $this->aclHelper : null);
        sort($expected);
        sort($childrenIds);

        $this->assertEquals($expected, $childrenIds);
    }

    /**
     * @return array
     */
    public function customerReferencesDataProvider()
    {
        return [
            'orphan' => [
                'customer.orphan',
                []
            ],
            'level_1' => [
                'customer.level_1',
                [
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                    'customer.level_1.1.2',
                    'customer.level_1.2',
                    'customer.level_1.2.1',
                    'customer.level_1.2.1.1',
                    'customer.level_1.3',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                ]
            ],
            'level_1.1' => [
                'customer.level_1.1',
                [
                    'customer.level_1.1.1',
                    'customer.level_1.1.2',
                ]
            ],
            'level_1.2' => [
                'customer.level_1.2',
                [
                    'customer.level_1.2.1',
                    'customer.level_1.2.1.1',
                ]
            ],
            'level_1.3' => [
                'customer.level_1.3',
                [
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                ]
            ],
            'level_1.4' => [
                'customer.level_1.4',
                [
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                ]
            ],
            'without acl' => [
                'customer.level_1',
                [
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                    'customer.level_1.1.2',
                    'customer.level_1.2',
                    'customer.level_1.2.1',
                    'customer.level_1.2.1.1',
                    'customer.level_1.3',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1'
                ],
                false
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCategoryCustomerIdsByVisibilityDataProvider()
    {
        return [
            'FIRST_LEVEL with VISIBLE' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => CustomerCategoryVisibility::VISIBLE,
                'expectedCustomers' => [
                    'customer.level_1.4',
                ]
            ],
            'FIRST_LEVEL with VISIBLE restricted' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => CustomerCategoryVisibility::VISIBLE,
                'expectedCustomers' => [],
                'restricted' => []
            ],
            'FIRST_LEVEL with HIDDEN' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => CustomerCategoryVisibility::HIDDEN,
                'expectedCustomers' => [
                    'customer.level_1.1',
                ]
            ],
        ];
    }

    public function testGetBatchIterator()
    {
        /** @var Customer[] $results */
        $results  = $this->repository->findAll();
        $customers = [];

        foreach ($results as $customer) {
            $customers[$customer->getId()] = $customer;
        }

        $customersQuantity = count($customers);
        $customersIterator = $this->repository->getBatchIterator();
        $iteratorQuantity = 0;
        foreach ($customersIterator as $customer) {
            ++$iteratorQuantity;
            unset($customers[$customer->getId()]);
        }

        $this->assertEquals($customersQuantity, $iteratorQuantity);
        $this->assertEmpty($customers);
    }
}
