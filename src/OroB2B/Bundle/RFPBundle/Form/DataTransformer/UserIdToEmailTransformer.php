<?php

namespace OroB2B\Bundle\RFPBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserIdToEmailTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $email
     * @throws TransformationFailedException
     * @return null|int
     */
    public function transform($email)
    {
        if (!$email) {
            return null;
        }

        $user = $this->getUserRepository()->findOneBy(['email' => $email]);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'User with email "%s" does not exist',
                $email
            ));
        }

        return $user->getId();
    }

    /**
     * @param mixed $value
     * @throws TransformationFailedException
     * @return string|null
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        // system configuration may return email string as default value, so we need to check this case too
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return $value;
        }

        $user = $this->getUserRepository()->find((int)$value);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'User with ID "%s" does not exist',
                $value
            ));
        }

        return $user->getEmail();
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        $userClass = 'OroUserBundle:User';

        return $this->registry->getManagerForClass($userClass)->getRepository($userClass);
    }
}
