<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class MassRebuildCombinedPriceListsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new MassRebuildCombinedPriceListsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            [
                'rawBody' => [
                    'assignments' => [
                        [],
                        [
                            'force' => true,
                        ],
                        [
                            'website' => 1,
                        ],
                        [
                            'customer' => 1,
                        ],
                        [
                            'customerGroup' => 1,
                        ],
                        [
                            'force' => true,
                            'website' => 1,
                            'customer' => 2,
                            'customerGroup' => 3
                        ]
                    ],
                ],
                'expectedMessage' => [
                    'assignments' => [
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => true,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => 1,
                            'customer' => null,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => 1,
                            'customerGroup' => null
                        ],
                        [
                            'force' => false,
                            'website' => null,
                            'customer' => null,
                            'customerGroup' => 1
                        ],
                        [
                            'force' => true,
                            'website' => 1,
                            'customer' => 2,
                            'customerGroup' => 3
                        ]
                    ],
                ]
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'Invalid assignments ' => [
                'body' => ['assignments' => ['any']],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The value of the option "assignments" is expected to be of type array of array, ' .
                    'but is of type array of "string"./'
            ],
            'Invalid "force" option' => [
                'body' => ['assignments' => [['force' => 'invalid type']]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "assignments\[0\]\[force\]" with value "invalid type" ' .
                    'is expected to be of type "bool", but is of type "string"./'
            ],
            'Invalid "website" option' => [
                'body' => ['assignments' => [['website' => 'invalid type']]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "assignments\[0\]\[website\]" with value "invalid type" ' .
                    'is expected to be of type "null" or "int", but is of type "string"./'
            ],
            'Invalid "customer" option' => [
                'body' => ['assignments' => [['customer' => 'invalid type']]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "assignments\[0\]\[customer\]" with value "invalid type" ' .
                    'is expected to be of type "null" or "int", but is of type "string"./'
            ],
            'Invalid "customerGroup" option' => [
                'body' => ['assignments' => [['customerGroup' => 'invalid type']]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "assignments\[0\]\[customerGroup\]" with value "invalid type" ' .
                    'is expected to be of type "null" or "int", but is of type "string"./'
            ],
            'Invalid option in any array' => [
                'body' => ['assignments' => [[], ['force' => 'invalid type']]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "assignments\[1\]\[force\]" with value "invalid type" ' .
                    'is expected to be of type "bool", but is of type "string"./'
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        $data = [
            'assignments' => [
                [
                    'force' => true,
                    'website' => 1,
                    'customer' => 2,
                    'customerGroup' => 3,
                ],
            ],
        ];
        self::assertSame(
            'oro_pricing.price_lists.cpl.mass_rebuild:' . md5(json_encode($data)),
            $this->getTopic()->createJobName($data)
        );
    }
}
