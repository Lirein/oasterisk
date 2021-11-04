<?php

namespace core;

class StreamRecorderPlugin extends \view\ViewPort {

  public static function getViewLocation() {
    return 'streamrecorder';
  }

  public static function check() {
    return true;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        if(!isSet(data)) data = {};
        this.recorder = null;
        this.onData = null;
        this.onVisual = null;
        this.onError = null;
        this.value = null;
        this.audioCtx = new AudioContext();
      }

      function setValue(data) {
      }

      function getValue() {
        return this.value;
      }

      async function start() {
        if(!this.recorder) {
          let microphone = null;
          try {
            microphone = await navigator.mediaDevices.getUserMedia({
              audio: true
            });
          } catch(err) {
            if(this.onError) this.onError(this, err);
            return false;
          }
          if(microphone) {
            this.recorder = RecordRTC(microphone, {
                type: 'audio',
                desiredSampRate: 24000,
                recorderType:  RecordRTC.StereoAudioRecorder,
                timeSlice: 300, // pass this parameter
                ondataavailable: this.audioAvailable,
                numberOfAudioChannels: 1
            });

            this.recorder.microphone = microphone;
            const source = this.audioCtx.createMediaStreamSource(microphone);
            this.analyser = this.audioCtx.createAnalyser();
            this.analyser.fftSize = 2048;

            source.connect(this.analyser);

            this.visualDraw();
          } else {
            console.error('Unable to capture your microphone. Please check console logs.');
            return false;
          }
          this.value = null;
          this.recorder.startRecording();
        }
        return true;
      }

      function stop() {
        let p = Promise.resolve({then: (resolve) => {
          if(this.recorder) {
            this.recorder.stopRecording(async () => {
              this.recorder.microphone.stop();
              this.value = await this.recorder.getBlob();
              this.analyser.disconnect();
              this.analyser = null;
              this.recorder = null;
              resolve(true);
            });
          } else {
            resolve(false);
          }
        }});
        return p;
      }

      async function audioAvailable(blob) {
        let data = await blob.arrayBuffer();
        let pcm = data.slice(44);
        // let dataview = new DataView(data);
        // let size = (dataview.getUint32(4, true)-44)/2;
        // let channels = dataview.getUint16(22, true);
        // let samplerate = dataview.getUint16(24, true);
        // let byterate = dataview.getUint32(28, true);
        // let blockalign = dataview.getUint16(32, true);
        // let bitspersample = dataview.getUint16(34, true);
        // console.log({size: size, channels: channels, samplerate: samplerate, byterate: byterate, blockalign: blockalign, bitspersample: bitspersample});
        data = null;

        if(this.onData) {
          await this.onData(this, pcm);
        }
      }

      function visualDraw() {
        if(this.analyser) {
          requestAnimationFrame(this.visualDraw);
          const bufferLength = this.analyser.frequencyBinCount;
          const dataArray = new Uint8Array(bufferLength);
          this.analyser.getByteTimeDomainData(dataArray);

          let volume = 0;

          for(let i = 0; i < bufferLength; i++) {

            volume += Math.abs(dataArray[i]-128.0);
            
          }
          volume = (volume/bufferLength)*2/128*100;
          if(this.onVisual) this.onVisual(this, volume);
        }
      }

      </script>
    <?php
  }
}