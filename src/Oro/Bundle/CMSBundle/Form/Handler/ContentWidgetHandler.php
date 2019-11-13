<?php

namespace Oro\Bundle\CMSBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for the content widget form.
 */
class ContentWidgetHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    /** @var string */
    public const UPDATE_MARKER = 'formUpdateMarker';

    /** @var ManagerRegistry */
    private $registry;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof ContentWidget) {
            throw new \InvalidArgumentException('Argument data should be instance of ContentWidget entity');
        }

        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher->dispatch(Events::BEFORE_FORM_DATA_SET, $event);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($data);

        if (\in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            $updateMarker = $request->get(self::UPDATE_MARKER, false);

            $event = new FormProcessEvent($form, $data);
            $this->eventDispatcher->dispatch(Events::BEFORE_FORM_SUBMIT, $event);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $this->submitPostPutRequest($form, $request, !$updateMarker);

            if (!$updateMarker && $form->isValid()) {
                $this->onSuccess($data, $form);
                return true;
            }
        }

        return false;
    }

    /**
     * @param ContentWidget $contentWidget
     * @param FormInterface $form
     */
    private function onSuccess(ContentWidget $contentWidget, FormInterface $form): void
    {
        $manager = $this->registry->getManagerForClass(ContentWidget::class);
        $manager->persist($contentWidget);

        $this->eventDispatcher->dispatch(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $contentWidget));
        $manager->flush();
        $this->eventDispatcher->dispatch(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $contentWidget));
    }
}
