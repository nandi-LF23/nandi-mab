<template>
    <div :id="uniqid_gen" class='nifty_gauge' :style="'width:' + size + '; max-width:' + size" @click="emitClick">
        <div class='inner_wrapper'>
            <svg viewBox="0 0 100 100">
                <clipPath id="clip">
                    <path d="M 50 0 a 50 50 0 0 1 0 100 50 50 0 0 1 0 -100 v 16 a 34 34 0 0 0 0 68 34 34 0 0 0 0 -68" />
                </clipPath>
                <foreignObject x="0" y="0" width="100" height="100" clip-path="url(#clip)">
                    <div class='gradient' :style="buildConicGradient()" xmlns="http://www.w3.org/1999/xhtml"></div>
                </foreignObject>
            </svg>
        </div>
        <div class='gauge' :style="'transform:translate(-50%, -100%) rotate(' + capAngle() + 'deg);'"></div>
        <div class='knob'></div>
        <span v-if="subl" class='subl'>{{subl}}</span>
        <span v-if="subm" class='subm'>{{subm}}</span>
        <span v-if="subr" class='subr'>{{subr}}</span>
        <span v-if="label" class='label'>{{label}}</span>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
    </div>
</template>
<script>
export default {
    name: 'NiftyGauge',
    props: {
        angle:   { type: String, default: '0' },
        size:    { type: String, default: '' },
        tooltip: { type: String, default: '' },
        subm:    { type: String, default: '' },
        subl:    { type: String, default: '' },
        subr:    { type: String, default: '' },
        label:   { type: String, default: '' },
        stops:   {
            type: Array,
            default: [
                'from',    '90deg',
                '#f44336', '0%',  /* red */
                '#f44336', '50%', /* red */
                '#ffeb3b', '60%', /* yellow */
                '#4caf50', '75%', /* green */
                '#ffeb3b', '90%', /* yellow */
                '#f44336', '100%' /* red */
            ]
        }
    },
    data(){ return { uniqid_gen: '' } },
    created(){ this.uniqid_gen = this.uniqid('ngtt_'); },
    methods: {
        uniqid(prefix){ return prefix + window.Math.random().toString(36); },

        emitClick(){ this.$emit('clicked', this.uniqid_gen); },

        capAngle(){
            // if(this.angle > 90){ this.angle = 90; }
            // if(this.angle < -90){ this.angle = -90; }
            // return this.angle;

const centerX = 50;
const centerY = 50;
const radius = 35;
   this.angle = -0.5 * Math.PI - 2 * i * Math.PI;
   (centerX - radius * Math.cos(angle)).toFixed(4) + "%";
   (centerY + radius * Math.sin(angle)).toFixed(4) + "%";

            return this.angle;
        },

        lerp(start, end, zeroToOne) {
            return start + (end - start) * zeroToOne;
        },


        buildConicGradient(){

            /* conic gradient from <x>deg: 0deg is top, 90deg is right */
            /* conic gradient color percentage: 0-50% is half way around the circle (180 degrees) */

            let output = 'background-image:conic-gradient(';

            for(var i = 0; i < this.stops.length; i+= 2){
                output = output + this.stops[i] + ' ' + this.stops[i+1] + (i+2 < this.stops.length ? ', ' : '');
            }

            output = output + ')';

            //console.log(output);

            return output;
        }
    }
}
</script>
<style scoped>

.nifty_gauge {
    position: relative;
    margin:0 auto;
    margin-bottom:15%;
}

.nifty_gauge * {
    transition: all 1s ease-in-out;
}

.nifty_gauge .inner_wrapper {
    width:100%;
    height:0;
    padding-bottom:50%;
    overflow:hidden;
}

.nifty_gauge .inner_wrapper svg {
    width:100%;
}

.nifty_gauge .gradient {
    width:100%;
    height:100%;
}

.nifty_gauge .gauge {
    position:absolute;
    left:50%;
    top:100%;
    transform-origin:bottom center;
    transform:translate(-50%, -100%) rotate(0deg);
    background: black;
    width:6%;
    height:65%;
    border-radius:50% 50% 0% 0%;
}

.nifty_gauge .knob {
    position:absolute;
    left:50%;
    top:100%;
    transform:translate(-50%, -50%);
    background: black;
    width:13%;
    height:0;
    padding-bottom:13%;
    border-radius:100%;
}

.nifty_gauge .subl {
    position: absolute;
    text-align:left;
    top:115%;
    left:0%;
    width:100%;
    font-weight:bold;
    font-size: 0.9em;
}

.nifty_gauge .subm {
    position: absolute;
    text-align:center;
    top:115%;
    left:50%;
    transform:translateX(-50%);
    width:100%;
    font-weight:bold;
    font-size: 0.9em;
}

.nifty_gauge .subr {
    position: absolute;
    text-align:right;
    top:115%;
    left:100%;
    transform:translateX(-100%);
    width:100%;
    font-weight:bold;
    font-size: 0.9em;
}

.nifty_gauge .label {
    position: absolute;
    text-align:center;
    top:145%;
    left:50%;
    transform:translateX(-50%);
    width:100%;
    font-weight:bold;
    font-size: 0.9em;
}

@media(max-width:1024px){
    .nifty_gauge .label,
    .nifty_gauge .subl,
    .nifty_gauge .subm,
    .nifty_gauge .subr {
        font-size:0.8em;
    }
}

</style>
