<?php

namespace Oro\Bundle\CustomerBundle\Api\Processor\Create;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;

class SaveCustomerUser implements ProcessorInterface
{
    /**
     * @var CustomerUserManager
     */
    protected $userManager;

    /**
     * @param CustomerUserManager $userManager
     */
    public function __construct(CustomerUserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CreateContext $context */

        /** @var CustomerUser $user */
        $user = $context->getResult();
        if (!is_object($user)) {
            // entity does not exist
            return;
        }

        $this->userManager->updateUser($user);
    }
}
