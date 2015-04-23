<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\UserBundle\Entity\User;

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
     * @return null|int
     */
    public function transform($email)
    {
        if (!$email) {
            return null;
        }

        $user = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->findOneBy([
                'email' => $email
            ]);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'User with email "%s" does not exist!',
                $email
            ));
        }

        return $user->getId();
    }

    /**
     * @param int $userId
     * @return string|null
     */
    public function reverseTransform($userId)
    {
        if (!is_numeric($userId)) {
            return null;
        }

        $user = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->find($userId);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'User with ID "%s" does not exist!',
                $userId
            ));
        }

        return $user->getEmail();
    }
}
