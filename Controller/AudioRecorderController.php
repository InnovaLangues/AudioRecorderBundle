<?php

namespace Innova\AudioRecorderBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Innova\AudioRecorderBundle\Entity\AudioRecorderConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class AudioRecorderController extends Controller
{

    protected $arm;

    /**
     * @DI\InjectParams({
     *      "arm"         = @DI\Inject("innova.audio_recorder.manager")
     * })
     */
    public function __construct(AudioRecorderManager $arm)
    {
        $this->arm = $arm;
    }

    /**
    * @Route("/update/configuration/{id}", name="audio_recorder_config_save")
    * @ParamConverter("config", class="InnovaAudioRecorderBundle:AudioRecorderConfiguration")
    * @Method("POST")
    */
    public function updateConfigurationAction(AudioRecorderConfiguration $config, Request $request)
    {
      if ($request->isMethod('POST')) {
        $postData = $request->request->get('audio_recorder_configuration');
        if(isset($postData['max_try']) && isset($postData['max_recording_time'])){
            $this->arm->updateConfiguration($config, $postData);
            $msg = $this->get('translator')->trans('config_update_success', array(), 'tools');
            $this->get('session')->getFlashBag()->set('success', $msg);
        } else {
          $msg = $this->get('translator')->trans('config_update_error', array(), 'tools');
          $this->get('session')->getFlashBag()->set('error', $msg);
        }
        return $this->redirectToRoute('claro_desktop_open_tool', array('toolName' => 'home'));
      }
    }


}
