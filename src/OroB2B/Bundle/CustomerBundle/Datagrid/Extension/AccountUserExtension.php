<?php

namespace OroB2B\Bundle\CustomerBundle\Datagrid\Extension;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class AccountUserExtension extends AbstractExtension implements ContainerAwareInterface
{
    const ROUTE = 'orob2b_customer_frontend_account_user_datagrid_index';

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

        return is_a(
            $this->container->get('oro_security.security_facade')->getLoggedUser(),
            $this->container->getParameter('orob2b_customer.entity.account_user.class'),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->offsetSetByPath('[options][route]', self::ROUTE);
    }
}
