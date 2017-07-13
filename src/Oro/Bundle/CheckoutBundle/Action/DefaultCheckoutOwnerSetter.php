<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultCheckoutOwnerSetter
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ConfigManager  $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager  = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultOwner(Checkout $checkout)
    {
        if ($checkout->getOwner()) {
            return;
        }

        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
        );
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(User::class);
        $owner      = null;
        $ownerId    = $this->configManager->get($settingsKey);
        if ($ownerId) {
            /** @var User $owner */
            $owner = $repository->find($ownerId);
        }
        if (!$owner) {
            /** @var User $owner */
            $owner = $repository->findOneBy([]);
        }
        $checkout->setOwner($owner);
    }
}
