<template>
    <div :id="uniqid_gen" class='smss_container' :style="'min-width:' + size">
      <template v-if='accordion'>
        <div class='reading' v-show="status" :style="'background:' + calcReadingColor(status)" @click="expand = !expand">
          S: {{ status }}%
          <span class="arrow" :class="(expand ? ' down' : ' right')"></span>
        </div>
        <div class='reading' v-show="full && expand"   :style="'background:' + calcReadingColor(full)">F: {{ full }}%</div>
        <div class='reading' v-show="refill && expand" :style="'background:' + calcReadingColor(refill)">R: {{ refill }}%</div>
      </template>
      <template v-else>
        <div class='reading' v-show="full"   :style="'background:' + calcReadingColor(full)">F: {{ full }}%</div>
        <div class='reading' v-show="status" :style="'background:' + calcReadingColor(status)">S: {{ status }}%</div>
        <div class='reading' v-show="refill" :style="'background:' + calcReadingColor(refill)">R: {{ refill }}%</div>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
      </template>
    </div>
</template>
<script>

export default {
    name: 'SMStatusIndicator', // Soil Moisture Status Indicator (full, status, refill), simple boxes (Like SMLevelsIndicator)
    props: ['size', 'tooltip', 'full', 'status', 'refill', 'accordion'],
    data(){ return { uniqid_gen: '', expand: false } },
    created(){
      this.uniqid_gen = this.uniqid('smsstt_');
    },
    methods: {
    /* TODO: Add to Utils Mixin */
      uniqid(prefix){
        return prefix + window.Math.random().toString(36);
      },
      /*
      calcReadingColor(reading){
        if(!reading) return "black";
        if      (reading >=  0 && reading <  25 ) { return "red";    }
        else if (reading >= 25 && reading <  50 ) { return "orange"; }
        else if (reading >= 50 && reading <  75 ) { return "green"; }
        else if (reading >= 75 && reading <= 100) { return "blue";  }
        else                                      { return "gray"; }
      },
      */
      calcReadingColor(reading){
        if(!reading) return "black";
        if      (reading >=  0 && reading <  25 ) { return "linear-gradient(#FF0000, #8B0000)"; } /* Red */
        else if (reading >= 25 && reading <  50 ) { return "linear-gradient(#FFA500, #6F3D00)"; } /* Orange */
        else if (reading >= 50 && reading <  75 ) { return "linear-gradient(#008000, #005B00)"; } /* Green */
        else if (reading >= 75 && reading <= 100) { return "linear-gradient(#0000FF, #00009D)"; } /* Blue */
        else                                      { return "linear-gradient(#800080, #590059)"; } /* Purple */
      }
    }
}
</script>
<style scoped>

  .smss_container {
    margin:0;
  }  

  .smss_container .reading {
    /*font-family: 'Roboto Mono', monospace;*/
    font-family: 'Open Sans', sans-serif;
    padding:0.1rem 0.25rem;
    outline:1px solid #000;
    background-color:#333;
    border-radius:0.25rem;
    color:white;
    text-shadow: 1px 1px 1px #000000;
    min-width:4rem;
    text-align:left;
    white-space: nowrap;
  }

  .arrow {
    border: solid white;
    border-width: 0 2px 2px 0;
    display: inline-block;
    padding: 3px;
    margin-right: 3px;
  }

  .arrow:hover {
    cursor:pointer;
  }

  .right {
    transform: rotate(-45deg);
    -webkit-transform: rotate(-45deg);
  }

  .left {
    transform: rotate(135deg);
    -webkit-transform: rotate(135deg);
  }

  .up {
    transform: rotate(-135deg);
    -webkit-transform: rotate(-135deg);
    margin-bottom:2px;
  }

  .down {
    transform: rotate(45deg);
    -webkit-transform: rotate(45deg);
    margin-bottom:2px;
  }

</style>