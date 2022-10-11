<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RemoveDirectUrlForEntityTypeTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new RemoveDirectUrlForEntityTypeTopic();
    }

    public function validBodyDataProvider(): array
    {
        $className = get_class($this->createMock(SluggableInterface::class));

        return [
            ['body' => ['body' => $className], 'expectedBody' => ['body' => $className]],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "body" is missing./',
            ],
            'body has invalid type' => [
                'body' => ['body' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "body" with value stdClass is expected to be of type "string"/',
            ],
            'body contains missing class' => [
                'body' => ['body' => 'Acme'],
                'exceptionClass' => InvalidOptionsException::class,
                'expectedErrorMessage' => '/The option "body" was expected to contain FQCN of the class implementing/',
            ],
            'body contains class not implementing required interface' => [
                'body' => ['body' => \stdClass::class],
                'exceptionClass' => InvalidOptionsException::class,
                'expectedErrorMessage' => '/The option "body" was expected to contain FQCN of the class implementing/',
            ],
        ];
    }
}
