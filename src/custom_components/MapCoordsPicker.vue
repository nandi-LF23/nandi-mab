<template>
    <div class='mapcoordspicker'>
        <b-modal v-model="showModal" :title="modalTitle" centered size="xl">
            <div class='mapcontainer'>
                <MglMap
                    :repaint="true"
                    :mapboxGl="mapboxObj"
                    :accessToken="accessToken"
                    :mapStyle.sync="mapStyle"
                    :attributionControl="false"
                    :center="startPos"
                    logoPosition="bottom-right"
                    @load="mapLoaded"
                    @zoomend="updateCoords"
                    @dragend="updateCoords">
                    <MglNavigationControl position="top-right"/>
                    <MglGeolocateControl position="top-right" />

                    <template v-if="additionalLayers && additionalLayers.length">
                        <MglGeojsonLayer v-for="(obj, idx) in additionalLayers" :key="idx"
                            :sourceId="obj.layer.id"
                            :layerId="obj.layer.id"
                            :source="obj.source"
                            :layer="obj.layer"
                            :clearSource="false"/>
                    </template>

                </MglMap>
                <div class='xhair_c'></div>
                <div class='xhair_v'></div>
                <div class='xhair_h'></div>
            </div>
            <template #modal-footer="{ ok, cancel, hide }">
                <b-input-group prepend="Lat">
                    <b-form-input v-model="innerCoords.lat" placeholder="Latt" readonly></b-form-input>
                </b-input-group>
                <b-input-group prepend="Lng">
                    <b-form-input v-model="innerCoords.lng" placeholder="Lng" readonly></b-form-input>
                </b-input-group>
                <b-button variant="outline-primary" @click="cancel()">Cancel</b-button>
                <b-button variant="primary" @click="setCoords(ok)">Set</b-button>
            </template>
        </b-modal>
        <b-button variant="outline-primary" :block="fullWidthBtn" @click="showModal = !showModal">{{ buttonTitle }}</b-button>
    </div>
</template>
<script>

import MapboxGL from "mapbox-gl";
import MABMapStyle from '../assets/mapstyles/mab_mapstyle.min.js';
import { MglMap, MglNavigationControl, MglGeolocateControl, MglGeojsonLayer } from "v-mapbox";
import EasingFunctions from "../easingFunctions";

export default {
    name: 'MapCoordsPicker',
    components: {
        MglMap,
        MglNavigationControl,
        MglGeolocateControl,
        MglGeojsonLayer
    },
    props: {
        accessToken: { type: String,  required: true },
        modalTitle:  { type: String,  default: "Choose Coordinates" },
        buttonTitle: { type: String,  default: "Pick Coords" },
        navcontrols: { type: Boolean, default: true },
        geocontrols: { type: Boolean, default: true },
        fullWidthBtn:{ type: Boolean, default: false },
        startPos:    { type: Object,  default: () => { return { lat: 0, lng: 0}; } },
        additionalLayers: { type: Array, default: () => { return []; } }
    },
    data(){
        return {
            map: null,
            mapboxObj: null,
            mapStyle: null,
            mapActions: null,
            innerCoords: {
                lng: 0,
                lat: 0
            },
            showModal: false
        }
    },
    methods: {
        mapLoaded(e)
        {
            console.log("Map loaded..");
            e.map._isVue = true;
            MapboxGL._isVue = true;
            this.map = e.map;
            this.mapboxObj = MapboxGL;
            this.innerCoords = this.startPos;
            this.map.resize();
            this.mapActions = e.component.actions;

            this.mapActions.flyTo({
                zoom: 16,
                center: this.innerCoords,
                duration: 3000,
                animate: true,
                easing: EasingFunctions.easeInOutQuad,
                essential: true
            });
        },
        updateCoords()
        {
            // :coords.sync="coordsObj" in parent
            this.innerCoords = this.map.getCenter();
        },
        setCoords(ok)
        {
            ok();
            this.$emit('setCoords', this.innerCoords ); // {lng: 0, lat: 0}.
        }
    },
    
    mounted(){
        this.mapStyle = MABMapStyle;
    }
}
</script>
<style scoped>

.mapboxgl-canvas {
    width:100%;
    height:auto;
}

.mapcontainer {
    position:relative;
    width:100%;
    height:400px;
}

.xhair_v {
    position:absolute;
    width:1px;
    height:60px;
    background-color:rgb(0,255,0);
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    pointer-events:none;
}
.xhair_h {
    position:absolute;
    height:1px;
    width:60px;
    background-color:rgb(0,255,0);
    left:50%;
    top:50%;
    transform:translate(-50%, -50%);
    pointer-events:none;
}
.xhair_c {
    position:absolute;
    width:30px;
    height:30px;
    border:1px solid rgb(0,255,0);
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    pointer-events:none;
    border-radius:100%;
}
</style>