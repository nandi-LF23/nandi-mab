<template>
    <div class='mappolydrawer'>
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
                    @load="mapLoaded">
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
            </div>
            <template #modal-footer="{ ok, cancel, hide }">
                <b-button variant="outline-primary" @click="cancelPolyDraw(cancel)">Cancel</b-button>
                <b-button variant="primary" @click="savePolyData(ok)">Save</b-button>
            </template>
        </b-modal>
        <b-button variant="outline-primary" :block="fullWidthBtn" @click="showModal = !showModal">{{ buttonTitle }}</b-button>
    </div>
</template>
<script>

import MapboxGL from "mapbox-gl";
import MABMapStyle from '../assets/mapstyles/mab_mapstyle.min.js';
import MapboxDraw from "@mapbox/mapbox-gl-draw";
import '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css';
import { MglMap, MglNavigationControl, MglGeolocateControl, MglGeojsonLayer } from "v-mapbox";
import EasingFunctions from "../easingFunctions";

export default {
    name: 'mappolydrawer',
    components: {
        MglMap,
        MglGeojsonLayer,
        MglNavigationControl,
        MglGeolocateControl
    },
    props: {
        accessToken: { type: String,  required: true },
        modalTitle:  { type: String,  default: "Draw Outline" },
        buttonTitle: { type: String,  default: "Set Outline" },
        navcontrols: { type: Boolean, default: true },
        geocontrols: { type: Boolean, default: true },
        fullWidthBtn:{ type: Boolean, default: false },
        startPos:    { type: Object,  default: { lat: 0, lng: 0} },
        polyData:    { type: Object,  default: {} },
        additionalLayers: { type: Array, default: [] },
    },
    data(){
        return {
            map: null,
            mapDraw: null,
            mapboxObj: null,
            mapStyle: null,
            mapActions: null,
            showModal: false
        }
    },
    methods: {
        mapLoaded(e)
        {
            e.map._isVue = true;
            MapboxGL._isVue = true;

            let isPopulated = this.polyData && Object.keys(this.polyData).length !== 0;

            this.map = e.map;
            this.mapboxObj = MapboxGL;
            this.map.resize();
            this.mapActions = e.component.actions;

            this.mapDraw = new MapboxDraw({
                displayControlsDefault: false,
                controls: {
                    polygon: true,
                    trash: true
                },
                defaultMode: isPopulated ? 'simple_select' : 'draw_polygon'
            });

            this.map.addControl(this.mapDraw, 'top-left');

            // for editing existing
            if(isPopulated){
                this.mapDraw.add(this.polyData);
            }

            this.mapActions.flyTo({
                zoom: 14,
                center: this.startPos,
                duration: 3000,
                animate: true,
                easing: EasingFunctions.easeInOutQuad,
                essential: true
            });
        },

        savePolyData(ok)
        {
            ok();
            this.$emit('savePolyData', this.mapDraw.getAll()); // emit FeatureCollection
        },

        cancelPolyDraw(cancel_func)
        {
            cancel_func();
            this.$emit('cancelPolyDraw');
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
    height:450px;
}

</style>