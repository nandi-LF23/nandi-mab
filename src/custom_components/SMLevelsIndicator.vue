<template>
    <div :id="uniqid_gen" class='sml_container' :style="'min-width:' + size">
        <!-- <div class='reading' v-show="marker.node_id">{{ marker.node_id }}</div> -->
        <div class='reading' v-show="sm1"  :style="'background:' + calcReadingColor(sm1)">{{ sm1 }}%</div>
        <div class='reading' v-show="sm2"  :style="'background:' + calcReadingColor(sm2)">{{ sm2 }}%</div>
        <div class='reading' v-show="sm3"  :style="'background:' + calcReadingColor(sm3)">{{ sm3 }}%</div>
        <div class='reading' v-show="sm4"  :style="'background:' + calcReadingColor(sm4)">{{ sm4 }}%</div>
        <div class='reading' v-show="sm5"  :style="'background:' + calcReadingColor(sm5)">{{ sm5 }}%</div>
        <div class='reading' v-show="sm6"  :style="'background:' + calcReadingColor(sm6)">{{ sm6 }}%</div>
        <div class='reading' v-show="sm7"  :style="'background:' + calcReadingColor(sm7)">{{ sm7 }}%</div>
        <div class='reading' v-show="sm8"  :style="'background:' + calcReadingColor(sm8)">{{ sm8 }}%</div>
        <div class='reading' v-show="sm9"  :style="'background:' + calcReadingColor(sm9)">{{ sm9 }}%</div>
        <div class='reading' v-show="sm10" :style="'background:' + calcReadingColor(sm10)">{{ sm10 }}%</div>
        <div class='reading' v-show="sm11" :style="'background:' + calcReadingColor(sm11)">{{ sm11 }}%</div>
        <div class='reading' v-show="sm12" :style="'background:' + calcReadingColor(sm12)">{{ sm12 }}%</div>
        <div class='reading' v-show="sm13" :style="'background:' + calcReadingColor(sm13)">{{ sm13 }}%</div>
        <div class='reading' v-show="sm14" :style="'background:' + calcReadingColor(sm14)">{{ sm14 }}%</div>
        <div class='reading' v-show="sm15" :style="'background:' + calcReadingColor(sm15)">{{ sm15 }}%</div>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
    </div>
</template>
<script>

export default {
    name: 'SMLevelsIndicator', // Soil Moisture Levels Indicator (stacked sm1..15 percentages)
    props: ['size', 'tooltip', 'sm1', 'sm2', 'sm3', 'sm4', 'sm5', 'sm6', 'sm7', 'sm8', 'sm9', 'sm10', 'sm11', 'sm12', 'sm13', 'sm14', 'sm15'],
    data(){ return { uniqid_gen: '' } },
    created(){
      this.uniqid_gen = this.uniqid('smltt_');
    },
    methods: {
    /* TODO: Add to Utils Mixin */
      uniqid(prefix){
        return prefix + window.Math.random().toString(36);
      },
      calcReadingColor(reading){
        if(!reading) return "black";
        if      (reading >=  0 && reading <  25 ) { return "linear-gradient(#FF0000, #8B0000)"; } /* Red */
        else if (reading >= 25 && reading <  50 ) { return "linear-gradient(#FFA500, #6F3D00)"; } /* Orange */
        else if (reading >= 50 && reading <  75 ) { return "linear-gradient(#008000, #005B00)"; } /* Green */
        else if (reading >= 75 && reading <= 100) { return "linear-gradient(#0000FF, #00009D)"; } /* Blue */
        else                                      { return "linear-gradient(#800080, #590059)"; } /* Purple */
      },
    }
}
</script>
<style scoped>

  .sml_container {
    margin:0;
  }  

  .sml_container .reading {
    /*font-family: 'Roboto Mono', monospace;*/
    font-family: 'Open Sans', sans-serif;
    padding:0.1rem 0.25rem;
    outline:1px solid #000;
    border-radius:0.25rem;
    background:#333;
    color:white;
    text-shadow: 1px 1px 1px #000000;
    text-align: center;
    white-space: nowrap;
  }

</style>