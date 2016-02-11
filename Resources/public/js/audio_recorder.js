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
var audios = [];// collection of audio objects
var aStream; // current recorder stream




function recordAudio() {

    captureUserMedia({
        audio: true
    }, function (audioStream) {

        $('#audio-record-start').prop('disabled', 'disabled');
        $('#audio-play').prop('disabled', 'disabled');
        $('#audio-record-stop').prop('disabled', '');
        $('#audio-download').prop('disabled', 'disabled');

        var options = {
            type: 'audio',
            bufferSize: 0,
            sampleRate: 44100
        };

        audioRecorder = RecordRTC(audioStream, options);

        audioRecorder.startRecording();
        gotStream(audioStream);

        aStream = audioStream;

        audioStream.onended = function () {
            console.log('stream ended');
        };
    }, function (error) {
        console.log(error);
    });
}

$('.modal').on('hide.bs.modal', function () {

    console.log('close modal');

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



function stopRecordingAudio() {
    var aRec = audioRecorder;
    audioRecorder.stopRecording(function (url) {
        cancelAnalyserUpdates();

        $('#audio-record-start').prop('disabled', '');
        $('#audio-play').prop('disabled', '');
        $('#audio-record-stop').prop('disabled', 'disabled');
        $('#audio-download').prop('disabled', '');

        audioObject = new Audio();
        audioObject.src = url;

        audios.push(audioObject);

        var html = '<div class="row" id="recorded-audio-row-' + aid.toString() + '">';
        html += '       <div class="col-md-8">';
        html += '         <div class="btn-group">';
        html += '           <button type="button" role="button" class="btn btn-default fa fa-play" data-id="' + aid + '" id="audio-play-' + aid.toString() + '"></button>';
        html += '           <button type="button" role="button" class="btn btn-default fa fa-stop" data-id="' + aid + '" id="audio-stop-' + aid.toString() + '"></button>';
        html += '           <button type="button" role="button" class="btn btn-danger fa fa-trash" data-id="' + aid + '" id="audio-delete-' + aid.toString() + '"></button>';
        html += '         </div>';
        html += '       </div>';
        html += '       <div class="col-md-4">';
        html += '         <input type="radio"  data-id="' + aid + '" name="audio-selected" onclick="audioSelected(this)">';
        html += '       </div>';
        html += '       <hr/>';
        html += '   </div>';
        $('#audio-records-container').append(html);

        aRecorders.push(aRec);

        $('#audio-play-' + aid.toString()).on('click', function () {
            var index = parseInt($(this).data('id'));
            audios[index].play();
            //aRecorders[index].save();
        });

        $('#audio-stop-' + aid.toString()).on('click', function () {
            var index = parseInt($(this).data('id'));
            audios[index].pause();
            audios[index].currentTime = 0;
        });

        $('#audio-delete-' + aid.toString()).on('click', function () {
            var index = parseInt($(this).data('id'));
            console.log('delete me ' + index.toString());
            audios.splice(index, 1);
            aRecorders.splice(index, 1);
            if (audios.length === 0) {
                $('#submitButton').prop('disabled', true);
            }

            $('#recorded-audio-row-' + index).remove();
        });

        aid++;

        // stop sharing microphone
        if (aStream)
            aStream.stop();

    });
}

function audioSelected(elem) {
    $('#submitButton').prop('disabled', false);
}

/*
// in my own controller
function uploadAudio() {
    // get selected audio index
    var index = -1;
    index = parseInt($('input:checked').data('id'));
    if (index > -1) {
        var recorder = aRecorders[index];
        var blob = recorder.getBlob();
        var formData = new FormData();

        var fileName = index.toString() + '-recorded';
        formData.append('filename', fileName);
        if (isFirefox) {
            formData.append('nav', 'firefox');
        } else {
            formData.append('nav', 'chrome');
        }
        formData.append('blob', blob);
        var route = Routing.generate('innova_audio_recorder_submit');
        //var route = Routing.generate('submit_resource_form', {resourceType:'file'});
        xhr(route, formData, null, function (fileURL) {});
    }
}
*/

// use with claro new Resource API
function uploadAudio() {
  console.log('yep');
    // get selected audio index
    var index = -1;
    index = parseInt($('input:checked').data('id'));
    if (index > -1) {
        console.log('yep2');
        var recorder = aRecorders[index];
        var blob = recorder.getBlob();
        var formData = new FormData();

        var fileName = index.toString() + '-recorded';
        /*formData.append('filename', fileName);
        if (isFirefox) {
            formData.append('nav', 'firefox');
        } else {
            formData.append('nav', 'chrome');
        }*/
        formData.append('file', blob);


        /*var fileFormData = {
          'file' : blob,
          'nav' : 'firefox',
          'name': 'fake-name'
        };*/

        //formData.append('file_form', fileFormData;


        //file_form[name] file_form[file]

        //formData.append('file', blob);
        //var route = Routing.generate('innova_audio_recorder_submit');
        // /api/resources/{resourceType}/parent/{parent}/encoding/{encoding}/
        // var route = Routing.generate('submit_resource_form', {'resourceType':'file', 'parent':0, 'encoding':'none'});
        var route = $('#submit-url').val();

        /*$.ajax({
          url :route,
          type: 'POST',
          data: fileFormData,
        }).done(function(data){
          console.log('done');
          console.log(data);
        });*/

        xhr(route, formData, null, function (fileURL) {});
    }
}


function xhr(url, data, progress, callback) {
    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
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
            // location.reload();

        }
    };
    request.upload.onprogress = function (e) {
        if (!progress)
            return;
        if (e.lengthComputable) {
            progress.value = (e.loaded / e.total) * 100;
            progress.textContent = progress.value;
        }
        if (progress.value === 100) {
            progress.value = 0;
        }
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

function drawLoop( time ) {

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
    analyserContext.clearRect(0,0,canvasWidth,canvasHeight);

    analyserContext.fillStyle = gradient;
    // draw a bar based on the current volume
    analyserContext.fillRect(0, 0, meter.volume * canvasWidth * 1.4, canvasHeight);

    // set up the next visual callback
    rafID = window.requestAnimationFrame( drawLoop );
}

function cancelAnalyserUpdates() {
    window.cancelAnimationFrame(rafID);
    // clear the current state
    analyserContext.clearRect(0, 0, canvasWidth, canvasHeight);
    rafID = null;
}
