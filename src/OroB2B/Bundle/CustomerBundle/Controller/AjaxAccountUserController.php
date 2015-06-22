<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxAccountUserController extends Controller
{
    /**
     * @Route("/confirm/{id}", name="orob2b_customer_account_user_confirm", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_customer_account_user_update")
     *
     * @param int $id
     * @return JsonResponse
     */
    public function confirmAction($id)
    {
        try {
            $userManager = $this->get('orob2b_account_user.manager');
            $user = $userManager->findUserBy(['id' => $id]);

            if (null === $user) {
                throw new \InvalidArgumentException(sprintf('Account user with id %d not found.', $id));
            }

            $userManager->confirmRegistration($user);
            $userManager->updateUser($user);

            $response = [
                'successful' => true,
                'message' => $this->getTranslator()->trans('orob2b.customer.controller.accountuser.confirmed.message')
            ];
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Confirm account user failed: %s: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            $response = ['successful' => false];
        }

        return new JsonResponse($response);
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
