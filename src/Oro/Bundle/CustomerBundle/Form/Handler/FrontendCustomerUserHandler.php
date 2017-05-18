<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class FrontendCustomerUserHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var CustomerUserManager */
    protected $userManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param CustomerUserManager $userManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        CustomerUserManager $userManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
    }

    /**
     * Process form
     *
     * @param CustomerUser $customerUser
     * @return bool True on successful processing, false otherwise
     */
    public function process(CustomerUser $customerUser)
    {
        $isUpdated = false;
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                if (!$customerUser->getId()) {
                    $website = $this->request->attributes->get('current_website');
                    if ($website instanceof Website) {
                        $customerUser->setWebsite($website);
                    }
                    $this->userManager->register($customerUser);
                }

                $this->userManager->updateUser($customerUser);

                $isUpdated = true;
            }
        }

        // Reloads the user to reset its username. This is needed when the
        // username or password have been changed to avoid issues with the
        // security layer.
        if ($customerUser->getId()) {
            $this->userManager->reloadUser($customerUser);
        }

        return $isUpdated;
    }
}
