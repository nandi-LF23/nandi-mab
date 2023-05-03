<template>
  <div class="image_crop_upload">

    <div class='image_crop_modal' v-if="showCropModal">
      <div>
        <cropper
          ref="cropper"
          class="cropper"
          :src="getImageData"
          :auto-zoom="true"
          :stencil-props="stencilProps"
          :default-size="defaultSize"
          >
        </cropper>
      </div>
      <div class='actions'>
        <button class='action_btn cancel' @click="cancelCrop">Cancel</button>
        <button class='action_btn confirm' @click="cropImage">Confirm</button>
      </div>
    </div>

    <div v-if="!getImageData">
      <input class='image_upload' type='file' @change="fileChange"/>
    </div>

    <div v-else class='image_wrapper'>
      <img class='preview' :src="getImageData">
      <div class='actions'>
        <div class='action_btn cancel' @click='clearFile'>Clear</div>
        <div class='action_btn edit' @click='showCropDialog'>Edit</div>
      </div>
    </div>

  </div>
</template>
<script>

import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

export default {
  name: "ImageCropUpload",
  
  components: {
    Cropper
  },

  props: {
    stencilProps: { type: Object, default: () => { return { handlers: {}, scalable: true, movable: true, aspectRatio: 16/10 } } },
    fileData: { type: String, default: '' },
    resize: { type: Object, default: () => { return { width: 'auto', height: 'auto' } } }
  },

  data(){
    return {
      file: null,
      imageData: this.fileData,
      showCropModal: false,
      imageCropped: false,
      imageResized: false
    }
  },

  computed: {
    getImageData(){
      return this.imageData ? this.imageData : this.fileData;
    }
  },

  methods: {

    // utility function: convert file data to base64
    toBase64(file)
    {
      return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.readAsDataURL(file);
          reader.onload = () => resolve(reader.result);
          reader.onerror = error => reject(error);
      });
    },

    // file selected, open cropper modal
    fileChange(e)
    {
      if(e.target.files.length){
        //console.log('fileChange');
        this.file = e.target.files[0];
        this.toBase64(this.file)
        .then((data) => {
          //console.log(data);
          this.imageData = data; // base64 image data
          this.showCropModal = true;
          this.imageCropped = false;
          this.imageResized = false;
        });
      }
    },

    // confirm cropped dimensions, generate cropped image
    cropImage()
    {
      const { canvas } = this.$refs.cropper.getResult();
      this.imageData = this.resizeImage(canvas, this.resize.width, this.resize.height);
      this.showCropModal = false;
      this.imageCropped = true;
      this.$emit("done", this.imageData);
    },

    // no crop, just resizes image
    cancelCrop()
    {
      let canvas = document.createElement('canvas');
      let context = canvas.getContext('2d');
      let image = new Image();
      image.crossOrigin = "Anonymous";
      image.onload = () => {
        canvas.width = image.width;
        canvas.height = image.height;
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        if(!this.imageResized){
          this.imageData = this.resizeImage(canvas, this.resize.width, this.resize.height);
        }
        this.$emit("done", this.imageData);
      };
      image.src = this.getImageData;

      this.showCropModal = false;
      this.imageCropped = false;
    },

    resizeImage(canvas, max_width = 'auto', max_height = 'auto')
    {
      // bail if no data
      if(!canvas) return false; 
      
      // bail if both width and height are set to auto (defaults / skip resizing)
      if(max_width == 'auto' && max_height == 'auto'){ return canvas.toDataURL("image/png"); }

      var sw, sh, wr, hr;
      var mw = max_width, mh = max_height;
      var iw = canvas.width, ih = canvas.height;

      if(mw && mw != 'auto' && iw >= mw){ sw = mw; wr = iw / mw; sh = ih / wr; }

      if(mh && mh != 'auto' && ih >= mh){ sh = mh; hr = ih / mh; sw = iw / hr; }

      this.resample(canvas, sw, sh, true);
      this.imageResized = true;

      return canvas.toDataURL("image/png");
    },

    /**
     * Hermite resize - fast image resize/resample using Hermite filter. 1 cpu version!
     * 
     * @param {HtmlElement} canvas
     * @param {int} width
     * @param {int} height
     * @param {boolean} resize_canvas if true, canvas will be resized. Optional.
     */

    resample(canvas, width, height, resize_canvas)
    {
      var width_source = canvas.width;
      var height_source = canvas.height;
      width = Math.round(width);
      height = Math.round(height);

      var ratio_w = width_source / width;
      var ratio_h = height_source / height;
      var ratio_w_half = Math.ceil(ratio_w / 2);
      var ratio_h_half = Math.ceil(ratio_h / 2);

      var ctx = canvas.getContext("2d");
      var img = ctx.getImageData(0, 0, width_source, height_source);
      var img2 = ctx.createImageData(width, height);
      var data = img.data;
      var data2 = img2.data;

      for (var j = 0; j < height; j++) {
          for (var i = 0; i < width; i++) {
              var x2 = (i + j * width) * 4;
              var weight = 0;
              var weights = 0;
              var weights_alpha = 0;
              var gx_r = 0;
              var gx_g = 0;
              var gx_b = 0;
              var gx_a = 0;
              var center_y = (j + 0.5) * ratio_h;
              var yy_start = Math.floor(j * ratio_h);
              var yy_stop = Math.ceil((j + 1) * ratio_h);
              for (var yy = yy_start; yy < yy_stop; yy++) {
                  var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half;
                  var center_x = (i + 0.5) * ratio_w;
                  var w0 = dy * dy; //pre-calc part of w
                  var xx_start = Math.floor(i * ratio_w);
                  var xx_stop = Math.ceil((i + 1) * ratio_w);
                  for (var xx = xx_start; xx < xx_stop; xx++) {
                      var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half;
                      var w = Math.sqrt(w0 + dx * dx);
                      if (w >= 1) {
                          //pixel too far
                          continue;
                      }
                      //hermite filter
                      weight = 2 * w * w * w - 3 * w * w + 1;
                      var pos_x = 4 * (xx + yy * width_source);
                      //alpha
                      gx_a += weight * data[pos_x + 3];
                      weights_alpha += weight;
                      //colors
                      if (data[pos_x + 3] < 255)
                          weight = weight * data[pos_x + 3] / 250;
                      gx_r += weight * data[pos_x];
                      gx_g += weight * data[pos_x + 1];
                      gx_b += weight * data[pos_x + 2];
                      weights += weight;
                  }
              }
              data2[x2] = gx_r / weights;
              data2[x2 + 1] = gx_g / weights;
              data2[x2 + 2] = gx_b / weights;
              data2[x2 + 3] = gx_a / weights_alpha;
          }
      }
      //clear and resize canvas
      if (resize_canvas === true) {
          canvas.width = width;
          canvas.height = height;
      } else {
          ctx.clearRect(0, 0, width_source, height_source);
      }

      //draw
      ctx.putImageData(img2, 0, 0);
    },

    showCropDialog()
    {
      this.showCropModal = true;
    },

    clearFile()
    {
      this.file = null;
      this.fileData = null;
      this.imageData = null;
      this.imageCropped = false;
      this.showCropModal = false;
      this.$emit("clear", '');
    },

    defaultSize({ imageSize, visibleArea }) {
      return {
        width:  (visibleArea || imageSize).width  / 2,
        height: (visibleArea || imageSize).height / 2,
      };
    }

  }
};
</script>

<style scoped>
  .image_crop_upload {
    padding: .625rem .75rem;
    border:1px solid #eee;
  }

  .image_crop_upload input[type='file'] {
    font-size: 0.7em;
  }

  .image_crop_upload .image_crop_modal {
    border:1px #aaa;
    position:absolute;
    background-color:rgba(0,0,0,0.5);
    border-radius:0.5em;
    padding:0.5em;
    display:block;
    left:50%;
    top:50%;
    width:75%;
    height:0;
    padding-bottom:75%;
    transform:translate(-50%, -50%);
    z-index:99;
  }

  .image_crop_upload .actions {
    opacity:0;
    display:flex;
    flex-flow:row;
    justify-content:center;
    position:absolute;
    bottom:0;
    left:50%;
    transform:translateX(-50%);
    width:100%;
    transition:all .5s ease-in-out;
  }

  .image_crop_upload:hover .actions {
    opacity:1;
  }

  .image_crop_upload .actions .action_btn {
    border:none;
    padding:0.75em 1.5em;
    font-size:0.7em;
    border-radius:0.25em;
    margin-bottom:2px;
    margin-left:0.25em;
    margin-right:0.25em;
    color:white;
    background-color:rgba(0,0,0,0.5);
  }

  .image_crop_upload .image_crop_modal .actions {
    opacity:0;
  }

  .image_crop_upload .image_crop_modal:hover .actions {
    opacity:1;
  }

  .image_crop_upload .image_crop_modal .actions .action_btn:hover {
    background-color:rgba(0,0,0,0.75);
    cursor:pointer;
  }

  .image_crop_upload .image_wrapper {
    position:relative;
    text-align:center;
  }

  .image_crop_upload .image_wrapper .edit {
    cursor:pointer;
  }

  .image_crop_upload .image_wrapper .cancel {
    cursor:pointer;
  }

  .image_crop_upload .image_wrapper .preview {
    width:auto;
    max-height:200px;
  }
</style>
