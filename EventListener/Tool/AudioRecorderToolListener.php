<?php

namespace Innova\AudioRecorderBundle\EventListener\Tool;


use JMS\DiExtraBundle\Annotation as DI;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Event\DisplayToolEvent;
use Doctrine\ORM\EntityManager;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Innova\AudioRecorderBundle\Form\Type\AudioRecorderConfigurationType;
use Innova\AudioRecorderBundle\Entity\AudioRecorderConfiguration;

/**
 *  @DI\Service()
 */
class AudioRecorderToolListener
{
  private $templating;
  private $container;
  private $em;

  /**
   * @DI\InjectParams({
   *      "em"                = @DI\Inject("doctrine.orm.entity_manager"),
   *      "templating"        = @DI\Inject("templating"),
   *      "container"         = @DI\Inject("service_container")
   * })
   */
  public function __construct(EntityManager $em, TwigEngine $templating, ContainerInterface $container)
  {
      $this->templating = $templating;
      $this->em = $em;
      $this->container = $container;
  }

  /**
   * @DI\Observe("open_tool_desktop_innova_audio_recorder_tool")
   *
   * @param DisplayToolEvent $event
   */
  public function onDesktopOpen(DisplayToolEvent $event)
  {

      $config = $this->em->getRepository('InnovaAudioRecorderBundle:AudioRecorderConfiguration')->findAll()[0];
      $form = $this->container->get('form.factory')->create(new AudioRecorderConfigurationType(), $config);
      $content = $this->templating->render(
          'InnovaAudioRecorderBundle::desktopTool.html.twig',
          array('form' => $form->createView(), 'id' => $config->getId())
      );
      $event->setContent($content);
      $event->stopPropagation();
  }

}
