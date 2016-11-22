<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class FrontendAccountUserHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var AccountUserManager */
    protected $userManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param AccountUserManager $userManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        AccountUserManager $userManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
    }

    /**
     * Process form
     *
     * @param AccountUser $accountUser
     * @return bool True on successful processing, false otherwise
     */
    public function process(AccountUser $accountUser)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                if (!$accountUser->getId()) {
                    $website = $this->request->attributes->get('current_website');
                    if ($website instanceof Website) {
                        $accountUser->setWebsite($website);
                    }
                    $this->userManager->register($accountUser);
                }

                $this->userManager->updateUser($accountUser);
                $this->userManager->reloadUser($accountUser);

                return true;
            }
        }

        return false;
    }
}
