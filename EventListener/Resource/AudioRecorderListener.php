<?php

namespace Innova\AudioRecorderBundle\EventListener\Resource;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;
use Claroline\CoreBundle\Event\CreateResourceEvent;
use Claroline\CoreBundle\Form\FileType;
use Claroline\CoreBundle\Entity\Resource\File;
use Claroline\CoreBundle\Manager\ResourceManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 *  @DI\Service()
 */
class AudioRecorderListener
{
    private $container;

    /**
     * @DI\InjectParams({
     *      "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @DI\Observe("open_innova_audio_recorder")
     * Fired when a ResourceNode of type AudioFile is opened
     *
     * @param \Claroline\CoreBundle\Event\OpenResourceEvent $event
     *
     * @throws \Exception
     */
    public function onOpen(OpenResourceEvent $event)
    {
        $resource = $event->getResource();
        $route = $this->container
                ->get('router')
                ->generate('claro_resource_open', array(
            'parentId' => $resource->getResourceNode()->getId(),
            'resourceType' => 'file',
                )
        );
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("create_form_innova_audio_recorder")
     *
     * @param CreateFormResourceEvent $event
     */
    public function onCreateForm(CreateFormResourceEvent $event)
    {
        // Create form POPUP

        $content = $this->container->get('templating')->render(
          'InnovaAudioRecorderBundle:AudioRecorder:form.html.twig'
        );
        $event->setResponseContent($content);
        $event->stopPropagation();
    }
}
