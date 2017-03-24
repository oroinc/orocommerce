<?php

namespace Oro\Bundle\FlatRateShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RenameFlatRateMethods extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @internal
     */
    const PREVIOUS_VERSION_IDENTIFIER_PREFIX = 'flat_rate';

    /**
     * @var MethodRenamingEventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    protected $methodIdentifierGenerator;

    /**
     * @var ChannelRepository
     */
    protected $repository;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->methodIdentifierGenerator = $container->get('oro_flat_rate_shipping.method.identifier_generator.method');
        $this->repository = $container->get('oro_entity.doctrine_helper')->getEntityRepository(Channel::class);
        $this->dispatcher = $container->get('oro_shipping.method.event.dispatcher.method_renaming');
        $this->installed = $container->hasParameter('installed') && $container->getParameter('installed');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->installed) {
            $channels = $this->findAllFlatRateIntegrations();
            foreach ($channels as $channel) {
                $this->dispatchFlatRateRenamingEvent($channel);
            }

            $manager->flush();
        }
    }

    /**
     * @return Channel[]
     */
    protected function findAllFlatRateIntegrations()
    {
        return $this->repository->findByType(FlatRateChannelType::TYPE);
    }

    /**
     * @param Channel $channel
     */
    protected function dispatchFlatRateRenamingEvent(Channel $channel)
    {
        $this->dispatcher->dispatch(
            $this->generatePreviousVersionOfFlatRateIdentifier($channel),
            $this->methodIdentifierGenerator->generateIdentifier($channel)
        );
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    protected function generatePreviousVersionOfFlatRateIdentifier(Channel $channel)
    {
        return static::PREVIOUS_VERSION_IDENTIFIER_PREFIX.$channel->getId();
    }
}
