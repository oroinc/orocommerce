<?php

namespace Oro\Bundle\CMSBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
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
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_DATA_SET);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($data);

        if (\in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            $updateMarker = $request->get(self::UPDATE_MARKER, false);

            $event = new FormProcessEvent($form, $data);
            $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_SUBMIT);

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

    private function onSuccess(ContentWidget $contentWidget, FormInterface $form): void
    {
        $manager = $this->registry->getManagerForClass(ContentWidget::class);
        $manager->persist($contentWidget);

        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $contentWidget), Events::BEFORE_FLUSH);
        $manager->flush();
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $contentWidget), Events::AFTER_FLUSH);
    }
}
