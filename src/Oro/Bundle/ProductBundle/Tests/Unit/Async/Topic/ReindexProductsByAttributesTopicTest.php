<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ReindexProductsByAttributesTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ReindexProductsByAttributesTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required options' => [
                'body' => [
                    ReindexProductsByAttributesTopic::ATTRIBUTE_IDS_OPTION => [1, 2],
                ],
                'expectedBody' => [
                    ReindexProductsByAttributesTopic::ATTRIBUTE_IDS_OPTION => [1, 2],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "attributeIds" is missing./',
            ],
            'invalid "attributeIds" type' => [
                'body' => [
                    ReindexProductsByAttributesTopic::ATTRIBUTE_IDS_OPTION => 'test'
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "attributeIds" with value "test" ' .
                    'is expected to be of type "int\[\]", but is of type "string"./',
            ],
            'invalid "attributeIds" array type' => [
                'body' => [
                    ReindexProductsByAttributesTopic::ATTRIBUTE_IDS_OPTION => ['test']
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "attributeIds" ' .
                    'with value array is expected to be of type "int\[\]",' .
                    ' but one of the elements is of type "string"./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_product.reindex_products_by_attributes',
            $this->getTopic()->createJobName([])
        );
    }
}
