<template>
    <div class='meter_reading'>
        <div v-if="before" class='prefix' v-html="before"></div>
        <div v-if="digits" v-for="(digit, index) in digits" class='digit' :key="index">{{digit}}</div>
        <div v-if="after" class='suffix' v-html="after"></div>
    </div>
</template>
<script>

export default {
    name: 'MeterReading', // Fancy Meter Reading
    props: ['reading', 'prefix', 'suffix'],
    data(){
        return {
            digits: []
        }
    },
    created(){
        this.digits = this.reading.toString().split("");
        this.before = this.prefix;
        this.after = this.suffix == 1 ? "cm<sup>3</sup>" : "gal";
    }
}
</script>
<style scoped>
    .meter_reading {
        display: flex;
        flex-flow: row nowrap;
        padding:0.5rem;
        margin:0.1rem 0;
        box-shadow: inset 0 0 10px #000;
        background: #222;
        border-radius: 0.25rem;
        border: 1px solid #333
    }

    .meter_reading .digit {
        display:flex;
        flex-flow:column;
        align-items:center;
        justify-content:center;
        margin-right: 2px;
        background: linear-gradient(to top, #999, #fff, #999);
        color: #111;
        font-weight:bold;
        font-family: 'Courier New', Courier, monospace;
        text-align:center;
        font-size:1rem;
        line-height:1;
        min-width:1.25rem;
    }

    .meter_reading .digit:last-child {
        margin-right: 0;
    }

    .meter_reading .prefix,
    .meter_reading .suffix {
        display:block;
        color:white;
        padding:0 0.3rem;
        line-height:2;
    }

</style>