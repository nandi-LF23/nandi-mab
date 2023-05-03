<template>
    <div :id="uniqid_gen" class='smv_container' :style="'width:' + size" @click="emitClick">
        <div class='smv'>
            <div class='smv_level full' :style="'bottom:' + (full   > 98 ? 98 : (full   < 0 ? 0 : full))   + '%; background:blue'">
                <div v-if="shownumbers" class='smv_value'>{{ full + '%' }}</div>
            </div>
            <div class='smv_level status' :style="'bottom:' + (status > 98 ? 98 : (status < 0 ? 0 : status)) + '%'">
                <div v-if="shownumbers" class='smv_value'>{{ status + '%' }}</div>
            </div>
            <div class='smv_level refill' :style="'bottom:' + (refill > 98 ? 98 : (refill < 0 ? 0 : refill)) + '%; background:red'">
                <div v-if="shownumbers" class='smv_value'>{{ refill + '%' }}</div>
            </div>
        </div>
        <div v-if="label" class='smv_container_label'>{{ label }} </div>
        <b-tooltip v-if="tooltip" :target="uniqid_gen">{{ tooltip }}</b-tooltip>
    </div>
</template>
<script>

export default {
    name: 'SMStatusGraph', // Soil Moisture Graph (full, status, refill), vertical
    props: ['full', 'refill', 'status', 'size', 'tooltip', 'shownumbers', 'label'],
    data(){ return { uniqid_gen: '' } },
    created(){ this.uniqid_gen = this.uniqid('smstt_'); },
    methods: {
        uniqid(prefix){ return prefix + window.Math.random().toString(36); },
        emitClick(){ this.$emit('clicked', this.uniqid_gen); }
    }
}
</script>
<style scoped>

.smv_container {
    margin:0;
}

.smv_container_label {
    width:100%;
    font-size:0.5rem;
    font-weight:bold;
    text-align:center;
    display:block;
}

/* Outside */
.smv {
    position:relative;
    display:block;
    width:100%;
    height:0;
    padding-bottom:300%;
    background:black;
}

/* Inner Background */
.smv:after {
    position:absolute;
    content:"";
    display:block;
    top:2px;
    left:2px;
    bottom:2px;
    background:linear-gradient(to top, red, yellow, green, blue);
    width:calc(100% - 4px);
    height:calc(100% - 4px);
}

.smv_level {
    position: absolute;
    display:block;
    content: "";
    left:-15%;
    width:130%;
    height:3px;
    background:black;
    z-index:99;
}

.smv_level .smv_value {
    position:absolute;
    display:block;
    top:50%;
    left:0%;
    transform:translate(-100%, -50%);
    padding:0.2rem;
    background:black;
    color:white;
    font-size:0.5rem;
    line-height:1;
}

.smv_level.status .smv_value {
    right:0%;
}

</style>