<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PriceListSystemConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var string
     */
    private $priceListClassName;

    /**
     * @var string
     */
    private $managerForPriceList;

    /**
     * PriceListSystemConfigSubscriber constructor.
     * @param Registry $doctrine
     * @param string $priceListClassName
     */
    public function __construct(Registry $doctrine, $priceListClassName)
    {
        $this->doctrine = $doctrine;
    }


    public function serializeConfigCollection(ConfigSettingsUpdateEvent $event)
    {
        return $event;
    }

    public function unserializeConfigCollection(ConfigSettingsUpdateEvent $event)
    {
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigSettingsUpdateEvent::FORM_PRESET => 'unserializeConfigCollection',
            ConfigSettingsUpdateEvent::BEFORE_SAVE => 'serializeConfigCollection'
        ];
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|null|object
     */
    private function getManagerForPriceList()
    {
        if (!$this->managerForPriceList) {
            $manager = $this->doctrine->getManagerForClass($this->priceListClassName);

            if (!$manager) {
                throw new \InvalidArgumentException(
                    sprintf('Entity Manager for class %s doesn\'t exist.', $this->priceListClassName)
                );
            }
            $this->managerForPriceList = $manager;
        }

        return $this->managerForPriceList;
    }
}
