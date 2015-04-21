<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\UserBundle\Entity\User;

class UserToEmailTransformer implements DataTransformerInterface
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
     * @return null|User
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

        return $user;
    }

    /**
     * @param null|User $user
     * @return null|string
     */
    public function reverseTransform($user)
    {
        if (null === $user) {
            return null;
        }

        return $user->getEmail();
    }
}
