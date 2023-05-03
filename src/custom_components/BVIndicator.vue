<template>
    <div :id="uniqid_gen" class='bvv_container' :style="'width:' + size">
        <div class='bvv'>
            <!-- <div class='bvvlvl' :style="'height:' + (100 - (level < 0 ? 0 : (level > 100 ? 100 : level))) + '%'"></div> -->
            <div class='bvvlvl' :style="'height:' + (100 - (level < 0 ? 0 : (level > 100 ? 100 : level))) + '%'"></div>
            <svg class='charge' v-if="charging" stroke-width=".501" stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="50%" height="50%" viewBox="0 0 240 375"><path d="M90 375V225H0L150 0v150h90L90 375z" fill="#fff" fill-opacity=".750" stroke="none" font-family="Times New Roman" font-size="16"/></svg>
        </div>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
    </div>
</template>
<script>
export default {
    name: 'BVIndicator', // Battery Voltage Indicator
    props: ['level', 'size', 'charging', 'tooltip', 'battVoltage'],
    data(){ return { uniqid_gen: '', battLevel: 0 } },
    created(){
      this.uniqid_gen = this.uniqid('bvvtt_');
    },
    methods: {
      uniqid(prefix){
        return prefix + window.Math.random().toString(36);
      },
    }
}
</script>
<style scoped>

.bvv_container {
    margin:0;
}

.bvv_container::after {
    content:"B.V";
    width:100%;
    font-size:0.5rem;
    font-weight:bold;
    text-align:center;
    display:block;
}

/* Outside */
.bvv {
    position:relative;
    display:block;
    width:100%;
    height:0;
    padding-bottom:300%;
    background:black;
}

/* Charge */

.bvv .charge {
    position:absolute;
    left:50%;
    top:50%;
    transform:translate(-50%, -50%);
    z-index:99;
}

/* Tip */
.bvv:before {
    position:absolute;
    content: "";
    display: block;
    width: 50%;
    top: 0px;
    transform: translate(50%, -90%);
    height: 5%;
    background-color:black;
}

/* Inner Background */
.bvv:after {
    position:absolute;
    content:"";
    display:block;
    top:2px;
    left:2px;
    bottom:2px;
    background:linear-gradient(to top, red, orange, yellow, green);
    width:calc(100% - 4px);
    height:calc(100% - 4px);
}

/* Negative Level */
.bvvlvl {
    position: absolute;
    display:block;
    content: "";
    width:100%;
    top:0;
    height:50%;
    background:black;
    background:linear-gradient(90deg, rgba(0,0,0,1) 0%, rgba(35,35,35,1) 50%, rgba(0,0,0,1) 100%);
    z-index:99;
}

</style>