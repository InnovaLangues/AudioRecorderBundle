<?php

namespace Innova\AudioRecorderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Manager\ResourceManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Entity\Resource\File;
use Symfony\Component\Filesystem\Filesystem;
use JMS\DiExtraBundle\Annotation as DI;

/**
 *
 */
class AudioRecorderController
{
    protected $rm;
    protected $fileDir;
    protected $uploadDir;
    private $container;
    private $tokenStorage;

    /**
     * @DI\InjectParams({
     *      "container" = @DI\Inject("service_container"),
     *      "rm"         = @DI\Inject("claroline.manager.resource_manager"),
     *      "fileDir"    = @DI\Inject("%claroline.param.files_directory%"),
     *      "uploadDir"  = @DI\Inject("%claroline.param.uploads_directory%"),
     * })
     */
    public function __construct(ContainerInterface $container, ResourceManager $rm, $fileDir, $uploadDir)
    {
        $this->rm = $rm;
        $this->fileDir = $fileDir;
        $this->uploadDir = $uploadDir;
        $this->rm = $rm;
        $this->container = $container;
        $this->tokenStorage = $container->get('security.token_storage');
    }

    /**
     * @Route("/add", name="innova_audio_recorder_submit", options={"expose" = true})
     * @Method("POST")
     */
    public function submitFormAction(Request $request)
    {
        $formData = $request->request->all();

        // nav should be mandatory
        /*if (isFirefox) {
            formData.append('nav', 'firefox');
        } else {
            formData.append('nav', 'chrome');
        }
        // type should be mandatory
        formData.append('type', 'webrtc_audio');
        // convert is optionnal
        formData.append('convert', 'mp3');
        // file is mandatory
        formData.append('file', blob);*/


        $blob = $request->files->get('file');
        $size = $blob->getSize();
        // the output format we want (mp3 has been the choosen one)
        $encodedExt = 'mp3';
        // data received from request
        $isFirefox = $formData['nav'] === 'firefox';
        $uid = md5(uniqid());
        $ext = $isFirefox ? 'ogg' : 'wav';

        // the filename that will be in database (a human readable one should be better)
        $fileName = $uid.'.'.$ext;
        // the filename after encoding
        $encodedName = $uid.'.'.$encodedExt;

        // additional data
        $user = $this->tokenStorage->getToken()->getUser();
        $workspace = $user->getPersonalWorkspace();

        // upload file in temp dir
        $tempUploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
        $blob->move($tempUploadDir, $fileName);

        // encode file to mp3
        $cmd = 'avconv -i '.$tempUploadDir.DIRECTORY_SEPARATOR.$fileName.' -acodec libmp3lame -ab 128k '.$tempUploadDir.DIRECTORY_SEPARATOR.$encodedName;

        $output;
        $returnVar;
        $fs = new Filesystem();
        // $fs->chown($tempUploadDir.DIRECTORY_SEPARATOR.$fileName, 'www-data');
        // $fs->chmod($tempUploadDir.DIRECTORY_SEPARATOR.$fileName, '0777');
        exec($cmd, $output, $returnVar);

        // error
        if ($returnVar !== 0) {
          $content = array(
            'message' => 'Encoding error :-( with command ' . $cmd
          );

          return new JsonResponse($content, 500);
        }


        $uploadDir = $this->fileDir.DIRECTORY_SEPARATOR.'WORKSPACE_'.$workspace->getId();
        if (!$fs->exists($uploadDir)) {
            $fs->mkdir($uploadDir);
        }
        // the file name (Claroline Way)
        $claroName = $this->container->get('claroline.utilities.misc')->generateGuid().'.'.$encodedExt;
        // this is the path to the file (Field HashName ?)
        $hashName = 'WORKSPACE_'.$workspace->getId().DIRECTORY_SEPARATOR.$claroName;
        // copy the encoded file to right directory
        $fs->copy($tempUploadDir.DIRECTORY_SEPARATOR.$encodedName, $uploadDir.DIRECTORY_SEPARATOR.$claroName);

        $rt = $this->rm->getResourceTypeByName('file');
        if (!$rt) {
            $rt = new ResourceType();
            $rt->setName('file');
        }

        $file = new File();
        $file->setSize($size);
        $file->setName($encodedName);
        $file->setHashName($hashName);
        $file->setMimeType('audio/'.$encodedExt);
        $parent = $this->rm->getWorkspaceRoot($workspace);

        $resource = $this->rm->create($file, $rt, $user, $workspace, $parent);
        // remove temp files
        @unlink($tempUploadDir.DIRECTORY_SEPARATOR.$fileName);
        @unlink($tempUploadDir.DIRECTORY_SEPARATOR.$encodedName);

        return new JsonResponse('success', 200);
    }
}
