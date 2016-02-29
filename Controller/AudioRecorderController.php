<?php

namespace Innova\AudioRecorderBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\DiExtraBundle\Annotation as DI;

class AudioRecorderController
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

   
}
