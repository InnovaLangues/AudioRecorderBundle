<?php

namespace Innova\AudioRecorderBundle\Manager;

use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\File;
use Symfony\Component\HttpFoundation\File\File as sFile;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @DI\Service("innova.audio_recorder.manager")
 */
class AudioRecorderManager
{


    protected $rm;
    protected $fileDir;
    protected $tempUploadDir;
    protected $tokenStorage;
    protected $claroUtils;
    protected $container;



    /**
     * @DI\InjectParams({
     *      "container"   = @DI\Inject("service_container"),
     *      "rm"          = @DI\Inject("claroline.manager.resource_manager"),
     *      "fileDir"     = @DI\Inject("%claroline.param.files_directory%"),
     *      "uploadDir"   = @DI\Inject("%claroline.param.uploads_directory%")
     * })
     *
     * @param ResourceManager     $rm
     * @param String              $fileDir
     * @param String              $uploadDir
     */
    public function __construct(ContainerInterface $container, ResourceManager $rm, $fileDir, $uploadDir)
    {
        $this->rm = $rm;
        $this->container = $container;
        $this->fileDir = $fileDir;
        $this->tempUploadDir = $uploadDir;
        $this->tokenStorage = $container->get('security.token_storage');
        $this->claroUtils = $container->get('claroline.utilities.misc');
    }


    public function handleResourceCreation($postData, UploadedFile $blob){
      $errors = [];
      if(!$this->validateParams($postData, $blob)){
        $errors = array(
          'message' => 'some mandatory params are missing...'
        );
        return $errors;
      }

      $doEncode =  isset($postData['convert']) && $postData['convert'] == true;
      // additional data
      $user = $this->tokenStorage->getToken()->getUser();
      $workspace = $user->getPersonalWorkspace();

      $uploadDir = $this->fileDir.DIRECTORY_SEPARATOR.'WORKSPACE_'.$workspace->getId();
      $fs = new FileSystem();
      if (!$fs->exists($uploadDir)) {
          $fs->mkdir($uploadDir);
      }

      // data received from request
      $isFirefox = $postData['nav'] === 'firefox';
      $ext = $isFirefox ? 'ogg' : 'wav';
      // the filename that will be in database (a human readable one should be better)
      $fileName = $this->claroUtils->generateGuid().'.'.$ext;

      // this is the path to the file (original file) ToBe overriden if doEncode = true
      $hashName = 'WORKSPACE_'.$workspace->getId().DIRECTORY_SEPARATOR.$fileName;
      // file size @ToBe overriden if doEncode = true
      $size = $blob->getSize();

      if($doEncode){
        // the output format we want (mp3 has been the choosen one)
        $encodedExt = 'mp3';
        // the filename after encoding
        $encodedName = $this->claroUtils->generateGuid().'.'.$encodedExt;
        // upload original file in temp upload (ie web/uploads) dir
        $blob->move($this->tempUploadDir, $fileName);

        // encode original file to mp3
        $cmd = 'avconv -i '.$this->tempUploadDir.DIRECTORY_SEPARATOR.$fileName.' -acodec libmp3lame -ab 128k '.$this->tempUploadDir.DIRECTORY_SEPARATOR.$encodedName;
        $output;
        $returnVar;
        $fs = new Filesystem();
        exec($cmd, $output, $returnVar);

        // cmd error
        if ($returnVar !== 0) {
          $errors = array(
            'message' => 'Encoding error with command ' . $cmd
          );
          return $errors;
        }

        // this is the path to the file (encoded file)
        $hashName = 'WORKSPACE_'.$workspace->getId().DIRECTORY_SEPARATOR.$encodedName;

        // copy the encoded file to user workspace directory
        $fs->copy($this->tempUploadDir.DIRECTORY_SEPARATOR.$encodedName, $uploadDir.DIRECTORY_SEPARATOR.$encodedName);
        // get encoded file size...
        $sFile = new sFile($uploadDir.DIRECTORY_SEPARATOR.$encodedName);
        $size = $sFile->getSize();
        // remove temp encoded file
        @unlink($this->tempUploadDir.DIRECTORY_SEPARATOR.$encodedName);

      } else {
        $blob->move($uploadDir, $fileName);
      }

      $rt = $this->rm->getResourceTypeByName('file');
      if (!$rt) {
          $rt = new ResourceType();
          $rt->setName('file');
      }
      // create resource
      $file = new File();
      $file->setSize($size);
      $name = $doEncode ? $encodedName : $fileName;
      $file->setName($name);
      $file->setHashName($hashName);
      $finalExt = $doEncode ? $encodedExt : $ext;
      $file->setMimeType('audio/'.$finalExt);
      $parent = $this->rm->getWorkspaceRoot($workspace);

      $resource = $this->rm->create($file, $rt, $user, $workspace, $parent);
      // remove temp original file
      @unlink($this->tempUploadDir.DIRECTORY_SEPARATOR.$fileName);
      return $errors;
    }

    /**
    * Checks if the data sent by the Ajax Form contain all mandatory fields
    * @param Array  $postData
    */
    private function validateParams($postData, UploadedFile $file){

      $availableNavs = ["firefox", "chrome"];
      if(!isset($postData['nav']) || $postData['nav'] === '' || !in_array($postData['nav'], $availableNavs)){
        return false;
      }

      $availableTypes = ["webrtc_audio", "webrtc_video"];
      if(!isset($postData['type']) || $postData['type'] === '' || !in_array($postData['type'], $availableTypes)){
        return false;
      }

      if(!isset($file) || $file === null || !$file ){
        return false;
      }
      return true;
    }


}
