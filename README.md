# AudioFileBundle

This Bundle is a plugin intended to be used with [Claroline Connect LMS] (https://github.com/claroline/Claroline)
It allows you to create an audio file resource by uploading a file or recording something with a microphone and upload the result.

## Requirements
- This plugin uses HTML5 AudioContext and WebRTC
- Uses libavconv to convert audio to standard format
- Be sure to have a compatible browser before installing this plugin

## Installation

Install with composer :

   $ composer require innova/audio-file-bundle

   $ php app/console claroline:plugin:install AudioFileBundle

## Authors

* Donovan Tengblad (purplefish32)
* Axel Penin (Elorfin)
* Arnaud Bey (arnaudbey)
* Eric Vincent (ericvincenterv)
* Nicolas Dufour (eldoniel)
* Patrick Guillou (pitrackster)

## Licence

MIT
