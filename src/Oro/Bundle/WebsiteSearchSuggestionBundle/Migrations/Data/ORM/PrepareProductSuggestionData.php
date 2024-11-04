<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Initiates generation of website search suggestions for all products.
 */
class PrepareProductSuggestionData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->getMessageProducer()->send(GenerateSuggestionsTopic::getName(), []);
    }

    private function getMessageProducer(): MessageProducerInterface
    {
        return $this->container->get('oro_message_queue.client.message_producer');
    }
}
