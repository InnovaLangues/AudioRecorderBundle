<?php


namespace Innova\AudioRecorderBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Innova\AudioRecorderBundle\Entity\AudioRecorderConfiguration;

class DefaultData extends AbstractFixture
{
  public function load(ObjectManager $manager)
  {
    $config = new AudioRecorderConfiguration();
    $config->setMaxRecordingTime(0);
    $config->setMaxTry(0);

    $manager->persist($config);
    $manager->flush();
  }
}
