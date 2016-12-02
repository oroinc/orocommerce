<?php

namespace Oro\Bundle\FrontendBundle\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActionCurrentApplicationProvider implements CurrentApplicationProviderInterface
{
    use CurrentApplicationProviderTrait;

    const COMMERCE_APPLICATION = 'commerce';

    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        CurrentApplicationProviderInterface $applicationProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->applicationProvider = $applicationProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication()
    {
        return $this->isFrontend() ? self::COMMERCE_APPLICATION : $this->applicationProvider->getCurrentApplication();
    }

    /**
     * @return bool
     */
    protected function isFrontend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof AccountUser;
    }
}
