<?php

namespace Oro\Bundle\CustomerBundle\Datagrid\Extension;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class CustomerUserExtension extends AbstractExtension implements ContainerAwareInterface
{
    const ROUTE = 'oro_frontend_datagrid_index';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface not injected');
        }

        $customerUser = $this->container->get('oro_security.security_facade')->getLoggedUser();
        $customerUserClass = $this->container->getParameter('oro_customer.entity.customer_user.class');

        return !is_object($customerUser) || is_a($customerUser, $customerUserClass, true);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->offsetSetByPath('[options][route]', self::ROUTE);
    }
}
