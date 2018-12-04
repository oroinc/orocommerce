<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Extractor;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerUserExtractorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CustomerUserExtractor
     */
    private $extractor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extractor = new CustomerUserExtractor();
    }

    /**
     * @dataProvider extractProvider
     *
     * @param object|null       $entity
     * @param array             $customerUserMappings
     * @param CustomerUser|null $expectedResult
     */
    public function testExtract($entity, array $customerUserMappings, CustomerUser $expectedResult = null)
    {
        array_walk($customerUserMappings, function ($propertyPath, $className) {
            $this->extractor->addMapping($className, $propertyPath);
        });

        $result = $this->extractor->extract($entity);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function extractProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        return [
            'Extract with null argument' => [
                'entity' => null,
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ],
                'expectedResult' => null
            ],
            'Extract with Checkout entity argument with CustomerUser' => [
                'entity' =>  $this->getEntity(Checkout::class, [
                    'id' => 9,
                    'customerUser' => $customerUser
                ]),
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ],
                'expectedResult' => $customerUser
            ],
            'Extract with Checkout entity argument with empty CustomerUser' => [
                'entity' =>  $this->getEntity(Checkout::class, [
                    'id' => 9,
                ]),
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ],
                'expectedResult' => null
            ],
            'Extract with Request entity argument' => [
                'entity' =>  $this->getEntity(Request::class, [
                    'id' => 10,
                    'customerUser' => $customerUser
                ]),
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ],
                'expectedResult' => $customerUser
            ],
            'Extract with RequestProduct entity argument' => [
                'entity' =>  $this->getEntity(RequestProduct::class, [
                    'id' => 11,
                    'request' => $this->getEntity(Request::class, [
                        'id' => 10,
                        'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 1])
                    ])
                ]),
                'customerUserMappings' => [
                    Checkout::class => 'customerUser',
                    Request::class => 'customerUser'
                ],
                'expectedResult' => null
            ]
        ];
    }
}
