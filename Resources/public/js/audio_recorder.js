"use strict";




var isFirefox = !!navigator.mozGetUserMedia;

var audioObject;
var audioRecorder; // WebRtc object


var audioContext = new window.AudioContext();
var audioInput = null,
  realAudioInput = null,
  inputPoint = null;
var rafID = null;
var analyserContext = null;
var analyserNode = null;
var canvasWidth, canvasHeight;
var gradient;
var meter;


var aid = 0; // audio array current recording index
var aRecorders = []; // collection of recorders
var audios = []; // collection of audio objects
var aStream; // current recorder stream

var recordEndTimeOut = 750;


function recordAudio() {

  captureUserMedia({
    audio: true
  }, function(audioStream) {

    $('#audio-record-start').prop('disabled', 'disabled');
    $('#audio-record-stop').prop('disabled', '');

    var options = {
      type: 'audio',
      bufferSize: 0,
      sampleRate: 44100
    };

    audioRecorder = RecordRTC(audioStream, options);

    audioRecorder.startRecording();
    gotStream(audioStream);

    aStream = audioStream;

    audioStream.onended = function() {
      console.log('stream ended');
    };
  }, function(error) {
    console.log(error);
  });
}

$('.modal').on('shown.bs.modal', function(){
  console.log('modal shown');
  console.log($('#fake-form').attr('action'));
  parentNode = $('#fake-form').attr('action') !== '' ? $('#fake-form').attr('action') : 0;
  console.log('parent node id ' + parentNode);
});

$('.modal').on('hide.bs.modal', function() {

  console.log('modal closed');

  cancelAnalyserUpdates();

  if (aStream)
    aStream.stop();
  audios = [];
  aRecorders = [];

  audioContext = null;
  audioInput = null;
  realAudioInput = null;
  inputPoint = null;
  rafID = null;
  analyserContext = null;
  analyserNode = null;
  aStream = null;
  aid = 0;
});

function beforeSubmit(){
    uploadAudio();
    return false;
}

function stopRecordingAudio() {
  var aRec = audioRecorder;
  $('#audio-record-start').prop('disabled', '');
  $('#audio-record-stop').prop('disabled', 'disabled');

  // avoid recorded audio truncated end by setting a timeout
  window.setTimeout(function(){

    audioRecorder.stopRecording(function(url) {
      cancelAnalyserUpdates();
      audioObject = new Audio();
      audioObject.src = url;
      audios.push(audioObject);

      aRecorders.push(aRec);

      // recorded audio template
      var html = '<div class="row recorded-audio-row" id="recorded-audio-row-' + aid.toString() + '" data-index="' + aid + '">';
      html += '       <div class="col-md-8">';
      html += '         <div class="btn-group">';
      html += '           <button type="button" role="button" class="btn btn-default fa fa-play play" onclick="playAudio(this)"></button>';
      html += '           <button type="button" role="button" class="btn btn-default fa fa-stop stop" onclick="stopAudio(this)"></button>';
      html += '           <button type="button" role="button" class="btn btn-danger fa fa-trash delete" onclick="deleteAudio(this)"></button>';
      html += '         </div>';
      html += '       </div>';
      html += '       <div class="col-md-4">';
      html += '         <input type="radio" name="audio-selected" class="select" onclick="audioSelected(this)">';
      html += '       </div>';
      html += '       <hr/>';
      html += '   </div>';
      $('#audio-records-container').append(html);

      aid++;
      // stop sharing usermedia
      if (aStream){
        aStream.stop();
      }
    });
  }, recordEndTimeOut);
}

function audioSelected(elem) {
  $('#submitButton').prop('disabled', false);
}



function playAudio(elem) {
  var index = $(elem).closest('.recorded-audio-row').attr('data-index');
  audios[index].play();
}

function stopAudio(elem) {
  var index = $(elem).closest('.recorded-audio-row').attr('data-index');
  audios[index].pause();
  audios[index].currentTime = 0;
}

function deleteAudio(elem) {
  var index = $(elem).closest('.recorded-audio-row').attr('data-index');
  audios.splice(index, 1);
  aRecorders.splice(index, 1);

  $('#recorded-audio-row-' + index.toString()).remove();
  if (audios.length === 0) {
    $('#submitButton').prop('disabled', true);
  }

  // rebuilt all row id(s) and index
  $('.recorded-audio-row').each(function(i) {
    console.log('rebuilt row data-indexes');
    $(this).attr('id', 'recorded-audio-row-' + i.toString());
    $(this).attr('data-index', i);
  });

  aid = audios.length;
}


// use with claro new Resource API
function uploadAudio() {

  // get selected audio index
  var index = -1;
  index = $('input:checked').closest('.recorded-audio-row').attr('data-index');
  if (index > -1) {
    var recorder = aRecorders[index];
    var blob = recorder.getBlob();
    var formData = new FormData();
    // nav should be mandatory
    if (isFirefox) {
        formData.append('nav', 'firefox');
    } else {
        formData.append('nav', 'chrome');
    }
    // type should be mandatory
    formData.append('type', 'webrtc_audio');
    // convert is optionnal
    formData.append('convert', true);
    // file is mandatory
    formData.append('file', blob);
    var route = $('#arForm').attr('action');    
    xhr(route, formData, null, function(fileURL) {});
  }
}

function xhr(url, data, progress, callback) {
  var request = new XMLHttpRequest();

  var message = Translator.trans('creating_resource', {}, 'innova_audio_recorder');
  // tell the user that his action has been taken into account
  $('#submitButton').text(message);
  $('#submitButton').attr('disabled', true);

  request.onreadystatechange = function() {
    if (request.readyState === 4 && request.status === 200) {
      console.log('xhr end with success');
      audios = [];
      aRecorders = [];

      audioContext = null;
      audioInput = null;
      realAudioInput = null;
      inputPoint = null;
      rafID = null;
      analyserContext = null;
      analyserNode = null;
      aStream = null;
      aid = 0;
      // or generate route...
      location.reload();

    } else if(request.status === 500) {
      console.log('xhr error');
      console.log(request.response.message);
      $('#submitButton').text(Translator.trans('ok', {}, 'platform'));
      $('#submitButton').attr('disabled', false);
    }
  };

  request.upload.onprogress = function(e) {
    // if we want to use progress bar
  };

  request.open('POST', url);
  request.send(data);
}

function captureUserMedia(mediaConstraints, successCallback, errorCallback) {
  // needs adapter.js to work in chrome
  navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
}


function gotStream(stream) {
  inputPoint = audioContext.createGain();
  // Create an AudioNode from the stream.
  realAudioInput = audioContext.createMediaStreamSource(stream);

  meter = createAudioMeter(audioContext);
  realAudioInput.connect(meter);
  drawLoop();
}

function drawLoop(time) {

  if (!analyserContext) {
    var canvas = document.getElementById("analyser");
    canvasWidth = canvas.width;
    canvasHeight = canvas.height;
    analyserContext = canvas.getContext('2d');
    gradient = analyserContext.createLinearGradient(0, 0, canvasWidth, 0);
    gradient.addColorStop(0.15, '#ffff00'); // min level color
    gradient.addColorStop(0.80, '#ff0000'); // max level color
  }

  // clear the background
  analyserContext.clearRect(0, 0, canvasWidth, canvasHeight);

  analyserContext.fillStyle = gradient;
  // draw a bar based on the current volume
  analyserContext.fillRect(0, 0, meter.volume * canvasWidth * 1.4, canvasHeight);

  // set up the next visual callback
  rafID = window.requestAnimationFrame(drawLoop);
}

function cancelAnalyserUpdates() {
  window.cancelAnimationFrame(rafID);
  // clear the current state
  if(analyserContext){
    analyserContext.clearRect(0, 0, canvasWidth, canvasHeight);
  }
  rafID = null;
}
