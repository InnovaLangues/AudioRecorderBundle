# Innova\audio-recorder-bundle

This Bundle is a plugin intended to be used with [Claroline Connect LMS](https://github.com/claroline/Claroline)
It allows you to create a Claroline file resource by recording audio and upload the result.

It allows the user to record audio via an available audio input device (such as a laptop microphone) and create a *Claroline File* from the recorded audio blob.

You can choose if you want to convert the audio file or keep native format.

## Requirements
This plugin uses
- [WebRTC / RecordRTC](https://www.webrtc-experiment.com/RecordRTC/)
- [libav-tools](https://libav.org/) to convert audio to mp3 format

## Installation

Install with composer : ```$ composer require innova/audio-recorder-bundle```

## Limitations

Works on Chrome and Firefox

**Chrome needs an https connection to allow user media sharing!** See [this](https://sites.google.com/a/chromium.org/dev/Home/chromium-security/deprecating-powerful-features-on-insecure-origins) for more informations.

Some versions of Firefox / Linux could not read encoded mp3 but it works well with Firefox 44.0.2 

## Authors

* Donovan Tengblad (purplefish32)
* Axel Penin (Elorfin)
* Arnaud Bey (arnaudbey)
* Eric Vincent (ericvincenterv)
* Nicolas Dufour (eldoniel)
* Patrick Guillou (pitrackster)
* 

## Requests

Go to [Claroline](https://github.com/claroline/Claroline/issues) if you want to ask for new features.

Go to [Claroline Support](https://github.com/claroline/ClaroSupport/issues) if you encounter some bugs.

## Licence

MIT
