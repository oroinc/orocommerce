<?php

namespace OroB2B\Bundle\CheckoutBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvents;
use OroB2B\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Start checkout process on frontend
 *
 * Usage:
 *
 * @start_checkout:
 *     source_name: shoppingList
 *     source_entity: $.data
 *     data:
 *         currency: $.currency
 *     settings:
 *          allow_manual_source_remove: false
 *
 * source_name (required) is name of corresponding extended relation added to CheckoutSource
 * source_entity (required) is a source entity
 * settings (optional) are to WorkflowItem data and are used during checkout process
 * data (optional) is passed as properties of Checkout entity
 */
class StartCheckout extends AbstractAction
{
    const SOURCE_FIELD_KEY = 'source_name';
    const SOURCE_ENTITY_KEY = 'source_entity';
    const CHECKOUT_DATA_KEY = 'data';
    const SETTINGS_KEY = 'settings';
    const FORCE = 'force';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $checkoutClass;

    /**
     * @var string
     */
    protected $checkoutRoute;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var AbstractAction
     */
    protected $redirect;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param UserCurrencyManager $currencyManager
     * @param TokenStorageInterface $tokenStorage
     * @param PropertyAccessor $propertyAccessor
     * @param AbstractAction $redirect
     * @param EventDispatcherInterface $dispatcher
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        UserCurrencyManager $currencyManager,
        TokenStorageInterface $tokenStorage,
        PropertyAccessor $propertyAccessor,
        AbstractAction $redirect,
        EventDispatcherInterface $dispatcher,
        WorkflowManager $workflowManager
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->currencyManager = $currencyManager;
        $this->tokenStorage = $tokenStorage;
        $this->propertyAccessor = $propertyAccessor;
        $this->redirect = $redirect;
        $this->dispatcher = $dispatcher;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::SOURCE_FIELD_KEY])) {
            throw new InvalidParameterException('Source parameter is required');
        }

        if (empty($options[self::SOURCE_ENTITY_KEY])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param string $checkoutClass
     * @return StartCheckout
     */
    public function setCheckoutClass($checkoutClass)
    {
        $this->checkoutClass = $checkoutClass;

        return $this;
    }

    /**
     * @param string $checkoutRoute
     * @return StartCheckout
     */
    public function setCheckoutRoute($checkoutRoute)
    {
        $this->checkoutRoute = $checkoutRoute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $em = $this->getEntityManager();

        $sourceFieldName = $this->contextAccessor->getValue($context, $this->options[self::SOURCE_FIELD_KEY]);
        /** @var CheckoutSourceEntityInterface $sourceEntity */
        $sourceEntity = $this->contextAccessor->getValue($context, $this->options[self::SOURCE_ENTITY_KEY]);

        $sourceRepository = $em->getRepository('OroB2BCheckoutBundle:CheckoutSource');
        $checkoutSource = $sourceRepository->findOneBy([$sourceFieldName => $sourceEntity])
            ?: $this->createCheckoutSource($sourceFieldName, $sourceEntity);

        /** @var CheckoutInterface $checkout */
        list($checkout, $workflowName) = $this->getCheckoutWithWorkflowName($checkoutSource);
        
        $workflowItem = $this->getWorkflowItem($checkout, $workflowName);
        
        if (!$workflowItem) {
            $this->updateCheckoutData($context, $checkout);
            $em->persist($checkout);
            $em->flush($checkout);

            $workflowItem = $this->getWorkflowItem($checkout, $workflowName);

            $this->addWorkflowItemDataSettings($context, $workflowItem);
            $em->flush($workflowItem);
        } else {
            if ($this->getOptionFromContext($context, self::FORCE, false)) {
                $this->updateCheckoutData($context, $checkout);

                $this->addWorkflowItemDataSettings($context, $workflowItem);
                $em->flush();
            }
        }

        $this->redirect->initialize(
            [
                'route' => $this->checkoutRoute,
                'route_parameters' => ['id' => $checkout->getId(), 'checkoutType' => $checkout->getCheckoutType()]
            ]
        );
        $this->redirect->execute($context);
    }

    /**
     * @param mixed $context
     * @param WorkflowItem $workflowItem
     */
    protected function addWorkflowItemDataSettings($context, WorkflowItem $workflowItem)
    {
        $settings = $this->getOptionFromContext($context, self::SETTINGS_KEY, []);

        if (is_array($settings) && $settings) {
            $workflowData = $workflowItem->getData();
            foreach ($settings as $key => $setting) {
                $workflowData->set($key, $setting);
            }
            $workflowItem->setUpdated();
        }
    }

    /**
     * @param string $sourceFieldName
     * @param CheckoutSourceEntityInterface $sourceEntity
     * @return CheckoutSource
     */
    protected function createCheckoutSource($sourceFieldName, CheckoutSourceEntityInterface $sourceEntity)
    {
        $checkoutSource = new CheckoutSource();
        $this->propertyAccessor->setValue($checkoutSource, $sourceFieldName, $sourceEntity);

        return $checkoutSource;
    }

    /**
     * @param mixed $context
     * @param CheckoutInterface $checkout
     */
    protected function updateCheckoutData($context, CheckoutInterface $checkout)
    {
        /** @var AccountUser $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $account = $user->getAccount();
        $owner = $account->getOwner();
        $organization = $account->getOrganization();
        $defaultData = [
            'accountUser' => $user,
            'account' => $account,
            'owner' => $owner,
            'organization' => $organization,
            'website' => $this->websiteManager->getCurrentWebsite(),
            'currency' => $this->currencyManager->getUserCurrency()
        ];
        $this->setCheckoutData($context, $checkout, $defaultData);
    }

    /**
     * @param mixed $context
     * @param CheckoutInterface $checkout
     * @param array $defaultData
     */
    protected function setCheckoutData($context, CheckoutInterface $checkout, array $defaultData)
    {
        $data = $this->getOptionFromContext($context, self::CHECKOUT_DATA_KEY, []);
        $data = array_filter(
            $data,
            function ($element) {
                return $element !== null;
            }
        );
        $data = array_merge($defaultData, $data);

        foreach ($data as $property => $value) {
            if ($this->propertyAccessor->isWritable($checkout, $property)) {
                $this->propertyAccessor->setValue($checkout, $property, $value);
            }
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass($this->checkoutClass);
        }

        return $this->em;
    }

    /**
     * @param mixed $context
     * @param string $key
     * @param null|mixed $default
     * @return array|mixed
     */
    protected function getOptionFromContext($context, $key, $default = null)
    {
        $data = $default;
        if (array_key_exists($key, $this->options)) {
            $data = $this->contextAccessor->getValue($context, $this->options[$key]);
        }

        if ($data && is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->contextAccessor->getValue($context, $value);
            }
        }

        return $data;
    }

    /**
     * @param CheckoutSource $checkoutSource
     * @return array
     */
    protected function getCheckoutWithWorkflowName($checkoutSource)
    {
        $event = new CheckoutEntityEvent();
        $event->setSource($checkoutSource);
        $this->dispatcher->dispatch(CheckoutEvents::GET_CHECKOUT_ENTITY, $event);
        $checkout = $event->getCheckoutEntity();

        if (!$checkout) {
            $this->dispatcher->dispatch(CheckoutEvents::CREATE_CHECKOUT_ENTITY, $event);
            $checkout = $event->getCheckoutEntity();
        }

        if (!$checkout) {
            throw new \RuntimeException('Checkout entity should be specified.');
        }

        $workflowName = $event->getWorkflowName();

        if (!$workflowName) {
            throw new \RuntimeException('Workflow name for checkout entity should be specified.');
        }

        return [$checkout, $workflowName];
    }

    /**
     * @param CheckoutInterface $checkout
     * @param string $workflowName
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem(CheckoutInterface $checkout, $workflowName)
    {
        return $this->workflowManager->getWorkflowItem($checkout, $workflowName);
    }
}
