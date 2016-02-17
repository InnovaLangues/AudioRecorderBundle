<?php

namespace Innova\AudioRecorderBundle\Manager;

use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\File;
use Symfony\Component\HttpFoundation\File\File as sFile;
use Symfony\Component\Filesystem\Filesystem;
use Claroline\CoreBundle\Entity\Workspace\Workspace;

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
    protected $workspaceManager;

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
        $this->workspaceManager = $container->get('claroline.manager.workspace_manager');
    }   

    public function uploadFileAndCreateREsource($postData, UploadedFile $blob, Workspace $workspace = null)
    {
        // final file upload dir
        $targetDir = '';
        if (!is_null($workspace)) {
            $targetDir = $this->workspaceManager->getStorageDirectory($workspace);
        } else {
            $targetDir = $this->fileDir . DIRECTORY_SEPARATOR . $this->tokenStorage->getToken()->getUsername();
        }
        // if the taget dir does not exist, create it
        $fs = new FileSystem();
        if (!$fs->exists($targetDir)) {
          $fs->mkdir($targetDir);
        } 

        /*if (!is_dir($targetDir)) {
            mkdir($targetDir);
        }*/

        $doEncode = isset($postData['convert']) && $postData['convert'] == true;
        $isFirefox = $postData['nav'] === 'firefox';
        $extension = $isFirefox ? 'ogg' : 'wav';
        $encodingExt = 'mp3';
        $mimeType = $doEncode ? 'audio/' . $encodingExt : 'audio/' . $extension;

        $errors = [];
        if (!$this->validateParams($postData, $blob)) {
            $errors = array(
                'message' => 'some mandatory params are missing...'
            );
            return $errors;
        }
        
        // the filename that will be in database (a human readable one should be better)
        $fileBaseName = $this->claroUtils->generateGuid();
        $fileName = $fileBaseName . '.' . $extension;

        $hashNameWithoutExtension = $this->getFileHashNameWithoutExtension($fileBaseName, $workspace);
        $hashName = $doEncode ? $hashNameWithoutExtension . '.mp3' : $hashNameWithoutExtension . '.' . $extension;
        // file size @ToBe overriden if doEncode = true
        $size = $blob->getSize();

        if ($doEncode) {
            // the filename after encoding
            $encodedName = $fileBaseName . '.' . $encodingExt;
            // upload original file in temp upload (ie web/uploads) dir
            $blob->move($this->tempUploadDir, $fileName);

            // encode original file to mp3
            $cmd = 'avconv -i ' . $this->tempUploadDir . DIRECTORY_SEPARATOR . $fileName . ' -acodec libmp3lame -ab 128k ' . $this->tempUploadDir . DIRECTORY_SEPARATOR . $encodedName;
            $output;
            $returnVar;
            exec($cmd, $output, $returnVar);

            // cmd error
            if ($returnVar !== 0) {
                $errors = array(
                    'message' => 'Encoding error with command ' . $cmd
                );
                return $errors;
            }

            // copy the encoded file to user workspace directory
            $fs->copy($this->tempUploadDir . DIRECTORY_SEPARATOR . $encodedName, $targetDir . DIRECTORY_SEPARATOR . $encodedName);
            // get encoded file size...
            $sFile = new sFile($targetDir . DIRECTORY_SEPARATOR . $encodedName);
            $size = $sFile->getSize();
            // remove temp encoded file
            @unlink($this->tempUploadDir . DIRECTORY_SEPARATOR . $encodedName);
            // remove original non encoded file from temp dir
            @unlink($this->tempUploadDir . DIRECTORY_SEPARATOR . $fileName);
            
        } else {
            $blob->move($targetDir, $fileName);
        }

        $file = new File();
        $file->setSize($size);
        $name = $doEncode ? $encodedName:$fileName;
        $file->setName($name);
        $file->setHashName($hashName);
        $file->setMimeType($mimeType);

        return $file;
    }

    private function getFileHashNameWithoutExtension($fileBaseName, Workspace $workspace = null)
    {
        $hashName = '';
        if (!is_null($workspace)) {
            $hashName = 'WORKSPACE_' . $workspace->getId() . DIRECTORY_SEPARATOR . $fileBaseName;
        } else {
            $hashName = $this->tokenStorage->getToken()->getUsername() . DIRECTORY_SEPARATOR . $fileBaseName;
        }
        return $hashName;
    }

    /**
     * Checks if the data sent by the Ajax Form contain all mandatory fields
     * @param Array  $postData
     */
    private function validateParams($postData, UploadedFile $file)
    {

        $availableNavs = ["firefox", "chrome"];
        if (!isset($postData['nav']) || $postData['nav'] === '' || !in_array($postData['nav'], $availableNavs)) {
            return false;
        }

        $availableTypes = ["webrtc_audio", "webrtc_video"];
        if (!isset($postData['type']) || $postData['type'] === '' || !in_array($postData['type'], $availableTypes)) {
            return false;
        }

        if (!isset($file) || $file === null || !$file) {
            return false;
        }

        return true;
    }

}
