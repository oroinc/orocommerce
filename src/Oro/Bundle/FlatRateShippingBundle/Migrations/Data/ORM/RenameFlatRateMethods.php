<?php

namespace Oro\Bundle\FlatRateShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration rename FlatRate methods
 */
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
     * @var IntegrationIdentifierGeneratorInterface
     */
    protected $integrationIdentifierGenerator;

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
        $this->integrationIdentifierGenerator = $container
            ->get('oro_flat_rate_shipping.method.identifier_generator.method');
        $this->repository = $container->get('oro_entity.doctrine_helper')->getEntityRepository(Channel::class);
        $this->dispatcher = $container->get('oro_shipping.method.event.dispatcher.method_renaming');
        $this->installed = $container->get(ApplicationState::class)->isInstalled();
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
        return $this->repository->findBy(['type' => FlatRateChannelType::TYPE]);
    }

    protected function dispatchFlatRateRenamingEvent(Channel $channel)
    {
        $this->dispatcher->dispatch(
            $this->generatePreviousVersionOfFlatRateIdentifier($channel),
            $this->integrationIdentifierGenerator->generateIdentifier($channel)
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
