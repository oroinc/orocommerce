<?php

namespace Oro\Bundle\CustomerBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateFrontendHandler;
use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;

class FrontendCustomerUserRoleFormProvider extends AbstractFormProvider
{
    const CUSTOMER_USER_ROLE_CREATE_ROUTE_NAME = 'oro_customer_frontend_customer_user_role_create';
    const CUSTOMER_USER_ROLE_UPDATE_ROUTE_NAME = 'oro_customer_frontend_customer_user_role_update';

    /** @var CustomerUserRoleUpdateFrontendHandler */
    protected $handler;

    /**
     * @param FormFactoryInterface                 $formFactory
     * @param CustomerUserRoleUpdateFrontendHandler $handler
     * @param UrlGeneratorInterface                $router
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        CustomerUserRoleUpdateFrontendHandler $handler,
        UrlGeneratorInterface $router
    ) {
        parent::__construct($formFactory, $router);

        $this->handler = $handler;
    }

    /**
     * Get form accessor with customer user role form
     *
     * @param CustomerUserRole $customerUserRole
     *
     * @return FormView
     */
    public function getRoleFormView(CustomerUserRole $customerUserRole)
    {
        if ($customerUserRole->getId()) {
            $options['action'] = $this->generateUrl(
                self::CUSTOMER_USER_ROLE_UPDATE_ROUTE_NAME,
                ['id' => $customerUserRole->getId()]
            );
        } else {
            $options['action'] = $this->generateUrl(
                self::CUSTOMER_USER_ROLE_CREATE_ROUTE_NAME
            );
        }

        return $this->getFormView('', $customerUserRole, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function createForm($formName, $data = null, array $options = [])
    {
        $form = $this->handler->createForm($data);
        $this->handler->process($data);

        return $form;
    }
}
