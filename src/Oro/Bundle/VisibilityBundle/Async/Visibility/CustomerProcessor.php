<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCustomerTopic;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Updated visibility for a customer.
 */
class CustomerProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $doctrine;

    private CustomerPartialUpdateDriverInterface $partialUpdateDriver;

    public function __construct(
        ManagerRegistry $doctrine,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->doctrine = $doctrine;
        $this->partialUpdateDriver = $partialUpdateDriver;
        $this->logger = new NullLogger();
    }

    public static function getSubscribedTopics(): array
    {
        return [VisibilityOnChangeCustomerTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(BaseVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $customer = $this->getCustomer($body['id']);
            $this->partialUpdateDriver->updateCustomerVisibility($customer);
            $em->commit();
        } catch (UniqueConstraintViolationException $e) {
            $em->rollback();
            $this->logger->warning(
                'Couldn`t create scope because the scope already created with the same data.',
                ['exception' => $e]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => $e]
            );

            if ($e instanceof EntityNotFoundException) {
                return self::REJECT;
            }

            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * @throws EntityNotFoundException if a customer does not exist
     */
    public function getCustomer(int $customerId): Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->doctrine->getManagerForClass(Customer::class)
            ->find(Customer::class, $customerId);
        if (null === $customer) {
            throw new EntityNotFoundException('Customer was not found.');
        }

        return $customer;
    }
}
