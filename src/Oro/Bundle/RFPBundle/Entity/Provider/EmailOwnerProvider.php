<?php
namespace Oro\Bundle\RFPBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Provides RFP Request email owner.
 */
class EmailOwnerProvider implements EmailOwnerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass()
    {
        return Request::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManager $em, $email)
    {
        return $em->getRepository(Request::class)->findOneBy(['email' => $email]);
    }
}
