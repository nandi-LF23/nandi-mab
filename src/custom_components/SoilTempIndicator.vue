<template>
    <div :id="uniqid_gen" class='st_container' :style="'min-width:' + size">
        <!-- <div class='reading' v-show="marker.node_id">{{ marker.node_id }}</div> -->
        <div class='reading' v-show="t1"  v-html="formatTemp(t1)"></div>
        <div class='reading' v-show="t2"  v-html="formatTemp(t2)"></div>
        <div class='reading' v-show="t3"  v-html="formatTemp(t3)"></div>
        <div class='reading' v-show="t4"  v-html="formatTemp(t4)"></div>
        <div class='reading' v-show="t5"  v-html="formatTemp(t5)"></div>
        <div class='reading' v-show="t6"  v-html="formatTemp(t6)"></div>
        <div class='reading' v-show="t7"  v-html="formatTemp(t7)"></div>
        <div class='reading' v-show="t8"  v-html="formatTemp(t8)"></div>
        <div class='reading' v-show="t9"  v-html="formatTemp(t9)"></div>
        <div class='reading' v-show="t10" v-html="formatTemp(t10)"></div>
        <div class='reading' v-show="t11" v-html="formatTemp(t11)"></div>
        <div class='reading' v-show="t12" v-html="formatTemp(t12)"></div>
        <div class='reading' v-show="t13" v-html="formatTemp(t13)"></div>
        <div class='reading' v-show="t14" v-html="formatTemp(t14)"></div>
        <div class='reading' v-show="t15" v-html="formatTemp(t15)"></div>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
    </div>
</template>
<script>

export default {
    name: 'SoilTempIndicator', // Soil Moisture Levels Indicator (stacked sm1..15 percentages)
    props: ['size', 'tooltip', 'suffix', 'uom', 't1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9', 't10', 't11', 't12', 't13', 't14', 't15'],
    data(){ return { uniqid_gen: '' } },
    created(){
      this.uniqid_gen = this.uniqid('sttt_');
    },
    methods: {
      /* TODO: Add to Utils Mixin */
      uniqid(prefix){
        return prefix + window.Math.random().toString(36);
      },

      formatTemp(temp){
        // if fahrenheit, convert to celcius
        if(this.$props.uom == 2){ // 2 == fahrenheit
          temp = ((temp * 1.8) + 32).toFixed(2);
        }
        return this.$props.suffix ? temp + this.$props.suffix : temp;
      }
    }
}
</script>
<style scoped>

  .st_container {
    margin:0;
  }  

  .st_container .reading {
    /*font-family: 'Roboto Mono', monospace;*/
    font-family: 'Open Sans', sans-serif;
    padding:0.1rem 0.25rem;
    outline:1px solid #000;
    border-radius:0.25rem;
    /*background-color:#2e4a13;*/
    background:linear-gradient(#2e4a13, #21340E);
    color:white;
    text-shadow: 1px 1px 1px #000000;
    text-align: right;
    white-space: nowrap;
  }

</style>