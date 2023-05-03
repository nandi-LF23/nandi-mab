<template>
  <div class='map_container'>
    <FocusMenu @focusMenuChanged="toggleBurger" ref='focusmenu'>

      <template #header>
        <div class='search_wrapper clearfix'>
          <b-form-input :disabled="!visibleMarkers" v-model="fieldNameSearch" @input="doTextSearch()"
            class="form-control-flush fields_search" placeholder="Search...">
          </b-form-input>
          <div v-show="fieldNameSearch" class='fields_search_clear_btn' @click="fieldNameSearch = ''; filterFields();"></div>
        </div>

        <b-form-group class='type_filter_menu'>
          <b-form-checkbox-group v-model="nodeFilterType" :options="nodeFilterTypes" size="sm" style="width:100%" buttons
            button-variant="primary" @change="filterNodeType" v-on:click.stop.prevent="() => { }">
          </b-form-checkbox-group>
        </b-form-group>
      </template>

      <template v-for="(marker, index) in markers">
        <div :key="nodeFilterType.join('') + infoFilterType.join('') + index" v-if="marker.visible" class='field_record'
          :class="marker.node_address == selectedNode ? 'field_record_selected' : ''" @click="focusMarker(marker)">
          <div class='field_name'>{{ marker.field_name }}</div>
          <div class='node_address'>{{ marker.node_address }}</div>
          <div class='last_reading_date' :style="'color:' + calcLastReadingColor(marker)">{{ marker.date_time }}</div>
          <div v-if="marker.node_type == 'sm' && marker.status" class='field_sm_status'>Status: {{ marker.status }}%</div>
          <div class='indicators'>
            <SMStatusGraph v-if="marker.node_type == 'sm' && marker.status" :full="marker.full" :status="marker.status"
              :refill="marker.refill" :size="'35%'"
              :tooltip="'F: ' + marker.full + '%, S: ' + marker.status + '%, R: ' + marker.refill + '%'" :label="'S.M'"
              @clicked="goToGraph(marker)" />
            <div v-else style="width:35%"></div>
            <!-- <BVIndicator 
              :level="marker.battLevel"
              :size="'35%'"
              :charging="marker.charging"
              :tooltip="'Level: ' + marker.battLevel + '%'"/> -->
            <BVIndicator :level="marker.battLevel" :size="'35%'" :charging="marker.charging"
              :tooltip="'Voltage: ' + marker.battLevel + '%'" />
          </div>
        </div>
      </template>
    </FocusMenu>

    <MglMap :repaint="true" :mapboxGl="mapboxObj" :accessToken="$store.state.mapBoxAccessToken" :mapStyle.sync="mapStyle"
      :attributionControl="false" :center="startPos" logoPosition="bottom-right" @load="mapLoaded" @zoom="scaleMarkers"
      @zoomend="filterFields" @dragend="filterFields">

      <template v-for="marker in markers">

        <MglMarker @click="toggleMarkerPopup(marker)" v-if="marker.visible" :key="marker.node_address"
          :ref="marker.node_address" :coordinates="marker.position" anchor="top">

          <div class='field_marker' slot="marker">
            <div class='marker'
              :style="'color:' + calcMarkerIconColor(marker, marker.status, marker.node_type) + '; transform:scale(' + markerScale + '); transform-origin:top center;' + calcMarkerBorderColor(marker)">
              <template v-if="marker.node_type == 'well'">
                <svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible"
                  width="100%" height="100%" viewBox="0 0 165 187.5">
                  <path stroke-width="0"
                    d="M.001 187.5v-7.499h7.484v-52.499h7.482v-7.501h7.482V67.5H7.484v-7.499h14.965V39.002L7.484 45.001H0V30h7.484v.001l74.829-30 74.83 30h7.483v15.001h-7.484l-.001-.001-14.964-5.998.001 20.998h7.857v-.002h7.482V75H165v7.499h-7.483v.001h-7.482v-15h-7.857v52.501h7.482v7.501h7.483v52.499h7.482v7.499H.001zm119.728-59.998v-7.501h7.482V67.5h-14.967v7.501h-26.04v15h18.558l-7.484 30h-29.93l-7.483-30h18.857v-15h-26.34V67.5H37.416v52.501h7.482v7.501h74.831zM52.382 60.001v-7.499h59.862v7.499h14.967V33.002L82.313 15.001l-44.897 18v27h14.966z"
                    fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16" />
                </svg>
              </template>
              <template v-else-if="marker.node_type == 'meter'">
                <svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible"
                  width="100%" height="100%" viewBox="0 0 225 150">
                  <path stroke-width="0"
                    d="M43.746 105.001H22.499V120H0V29.999h22.499V45h21.246C55.326 18.519 81.762 0 112.498 0c30.735 0 57.171 18.519 68.752 45h21.249V29.999h22.499V120h-22.499v-14.999h-21.25C169.669 131.482 143.233 150 112.498 150c-30.736 0-57.172-18.518-68.752-44.999zM179.998 75c0-37.26-30.24-67.5-67.5-67.5-37.26 0-67.5 30.24-67.5 67.5 0 37.26 30.24 67.5 67.5 67.5 37.26 0 67.5-30.24 67.5-67.5zm-127.5 0c0-33.12 26.88-60 60-60 33.12 0 60 26.88 60 60 0 33.12-26.88 60-60 60-33.12 0-60-26.88-60-60zm37.5 22.5V82.501H74.999V97.5h14.999zm30-.001V82.5h-14.999v14.999h14.999zm30 0V82.5h-14.999v14.999h14.999zm.001-30V52.5H74.998v14.999h75.001z"
                    fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16" />
                </svg>
              </template>
              <template v-else-if="marker.node_type == 'sm'">
                <svg stroke-width=".501" stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg"
                  overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5">
                  <g stroke-linecap="round" stroke="none" fill="none" font-size="16">
                    <path
                      d="m37.499 187.5-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15Z"
                      fill="currentColor" />
                    <path
                      d="m7.177 124.653 15.222-1.48c.916 5.109 2.776 8.861 5.576 11.258 2.801 2.396 6.579 3.595 11.338 3.595 5.038 0 8.835-1.066 11.39-3.198 2.554-2.132 3.83-4.625 3.832-7.48-.002-1.831-.538-3.39-1.613-4.677-1.074-1.285-2.95-2.404-5.629-3.355-1.831-.635-6.008-1.762-12.527-3.383-8.386-2.079-14.27-4.635-17.653-7.665-4.757-4.263-7.135-9.461-7.135-15.592 0-3.946 1.119-7.637 3.356-11.072 2.237-3.437 5.462-6.053 9.673-7.85 4.211-1.796 9.294-2.695 15.247-2.695 9.726 0 17.046 2.132 21.962 6.395 4.915 4.264 7.496 9.954 7.743 17.072l-15.645.687c-.669-3.981-2.104-6.844-4.307-8.588-2.202-1.744-5.506-2.617-9.911-2.617-4.544 0-8.104.935-10.677 2.802-1.657 1.198-2.483 2.801-2.483 4.81 0 1.832.774 3.4 2.325 4.704 1.973 1.655 6.766 3.381 14.376 5.179 7.612 1.797 13.241 3.656 16.888 5.576 3.647 1.922 6.501 4.547 8.562 7.875 2.061 3.33 3.092 7.444 3.092 12.342 0 4.439-1.232 8.598-3.699 12.474-2.466 3.877-5.956 6.757-10.466 8.641-4.51 1.885-10.13 2.828-16.861 2.828-9.796 0-17.319-2.264-22.569-6.792-5.25-4.527-8.386-11.126-9.407-19.794Z"
                      fill-rule="nonzero" stroke-linejoin="round" stroke-width=".375" fill="#fff"
                      stroke-miterlimit="79.84" />
                  </g>
                </svg>

              </template>
              <template v-else-if="marker.node_type == 'nut'">
                <svg stroke-width=".501" stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg"
                  overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5">
                  <g stroke-linecap="round" stroke="none" fill="none" font-size="16">
                    <path
                      d="m37.499 187.5-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15Z"
                      fill="currentColor" />
                    <path
                      d="M9.001 147.09V75.155h14.132l29.441 48.039V75.155h13.493v71.935H51.494l-28.999-46.909v46.909H9.001Z"
                      fill-rule="nonzero" stroke-linejoin="round" stroke-width=".375" fill="#fff"
                      stroke-miterlimit="79.84" />
                  </g>
                </svg>
              </template>
            </div>
          </div>

          <MglPopup :closeButton="true" anchor="bottom" maxWidth="auto" :closeOnClick="false" @close="popupClosed">
            <div class='marker_info_wrap'>

              <div v-if="marker.node_type == 'well'" class='pump_control'>
                <div class='field_name'>{{ marker.field_name }}</div>
                <div class='node_address'>{{ marker.node_address }}</div>
                <div class='last_reading_date'>{{ marker.date_time }}</div>
                <div class='pump_control_inner'>
                  <b-form-checkbox :disabled="!userCan('Toggle', 'Well Controls', marker.id, 'O')"
                    v-model="marker.pump_status" switch size="lg" @change="togglePump(marker)">
                    {{ marker.pump_status ? 'Pump: On' : 'Pump: Off' }}
                  </b-form-checkbox>
                </div>
              </div>

              <div v-if="marker.node_type == 'meter'" class='meter_control'>
                <div class='field_name'>{{ marker.field_name }}</div>
                <div class='node_address'>{{ marker.node_address }}</div>
                <div class='last_reading_date'>{{ marker.date_time }}</div>
                <MeterReading :reading="marker.last_reading" :suffix="formatUOM(marker.measurement_type)"></MeterReading>
              </div>

              <div v-if="marker.node_type == 'sm'" class='sm_control'>
                <template v-for="(inner, idx) in markers">
                  <div v-if="
                    inner.base_node_address == marker.base_node_address &&
                    inner.node_type == 'sm' &&
                    inner.date_time != '1970-01-01 00:00:00' &&
                    (Math.abs(inner.latt - marker.latt) < 0.00001) &&
                    (Math.abs(inner.lng - marker.lng) < 0.00001)
                  " :key="idx" class='sm_control_item'
                    :class="inner.node_address == selectedNode ? 'selected_node' : ''">
                    <div class='field_name'>{{ inner.field_name }}</div>
                    <div class='node_address'>{{ inner.node_address }}</div>
                    <div class='last_reading_date'>{{ inner.date_time }}</div>
                    <div class='marker_info_horz'>
                      <SMStatusIndicator v-if="infoFilterType.includes('sm_status') && inner.status" :size="'5em'"
                        :full="inner.full" :status="inner.status" :refill="inner.refill" :accordion="true"
                        :showNumbers="true">
                      </SMStatusIndicator>

                      <SMLevelsIndicator v-if="infoFilterType.includes('sm_levels') && inner.sm1" :size="'5em'"
                        :tooltip="'S.M Levels'" :sm1="inner.sm1" :sm2="inner.sm2" :sm3="inner.sm3" :sm4="inner.sm4"
                        :sm5="inner.sm5" :sm6="inner.sm6" :sm7="inner.sm7" :sm8="inner.sm8" :sm9="inner.sm9"
                        :sm10="inner.sm10" :sm11="inner.sm11" :sm12="inner.sm12" :sm13="inner.sm13" :sm14="inner.sm14"
                        :sm15="inner.sm15">
                      </SMLevelsIndicator>

                      <SoilTempIndicator v-if="infoFilterType.includes('soil_temp') && inner.t1" :size="'5em'"
                        :tooltip="'Soil Temperatures'" :t1="inner.t1" :t2="inner.t2" :t3="inner.t3" :t4="inner.t4"
                        :t5="inner.t5" :t6="inner.t6" :t7="inner.t7" :t8="inner.t8" :t9="inner.t9" :t10="inner.t10"
                        :t11="inner.t11" :t12="inner.t12" :t13="inner.t13" :t14="inner.t14" :t15="inner.t15"
                        :suffix="$store.getters.getUOMEntity" :uom="$store.getters.getUOM">
                      </SoilTempIndicator>
                    </div>
                  </div>
                </template>
              </div>

              <div v-if="marker.node_type == 'nut'" class='sm_control'>
                <template v-for="(inner, idx) in markers">
                  <div v-if="
                    inner.base_node_address == marker.base_node_address &&
                    inner.node_type == 'nut' &&
                    inner.date_time != '1970-01-01 00:00:00' &&
                    (Math.abs(inner.latt - marker.latt) < 0.0001) &&
                    (Math.abs(inner.lng - marker.lng) < 0.0001)
                  " :key="idx" class='sm_control_item'
                    :class="inner.node_address == selectedNode ? 'selected_node' : ''">
                    <div class='field_name'>{{ inner.field_name }}</div>
                    <div class='node_address'>{{ inner.node_address }}</div>
                    <div class='last_reading_date'>{{ inner.date_time }}</div>
                    <div class='marker_info_horz'>
                    </div>
                  </div>
                </template>
              </div>

            </div>
          </MglPopup>
        </MglMarker>
      </template>
    </MglMap>

    <b-form-group class='info_filter_menu'>
      <b-form-checkbox-group v-model="infoFilterType" :options="infoFilterTypes" stacked buttons button-variant="primary"
        size="sm" @change="filterNodeType" v-on:click.stop.prevent="() => { }">
      </b-form-checkbox-group>
    </b-form-group>

    <base-button icon type="primary" class='legend_btn' size="sm" @click='toggleLegendModal()'><span
        class='cirle'>?</span></base-button>

    <b-modal v-model="showLegendModal" dialog-class='legend_modal' centered hide-backdrop no-close-on-esc
      no-close-on-backdrop>

      <template #modal-header="{ close }">
        <h6 slot="header" class="modal-title" id="modal-title-default">Legend</h6>
      </template>

      <template #default="{ hide }">
        <div class='legend_box'>
          <b-row>
            <b-col md>
              <h3>Soil Moisture Probes</h3>
              <div class='line'><span class='licon blue' v-html="renderSvgIcon('sm')"></span> 75%+ Moisture </div>
              <div class='line'><span class='licon green' v-html="renderSvgIcon('sm')"></span> 50-75% Moisture </div>
              <div class='line'><span class='licon orange' v-html="renderSvgIcon('sm')"></span> 25-50% Moisture </div>
              <div class='line'><span class='licon red' v-html="renderSvgIcon('sm')"></span> 0-25% Moisture </div>
            </b-col>
            <b-col md>
              <h3>Nutrient Probes</h3>
              <div class='line'><span class='licon blue' v-html="renderSvgIcon('nut')"></span> Coming Soon </div>
            </b-col>
          </b-row>
          <b-row>
            <b-col md>
              <h3>Wells</h3>
              <div class='line'><span class='licon green' v-html="renderSvgIcon('well')"></span> Pump Running </div>
              <div class='line'><span class='licon red' v-html="renderSvgIcon('well')"></span> Pump Idle </div>
            </b-col>
          </b-row>
          <b-row>
            <b-col md>
              <h3>Meters</h3>
              <div class='line'><span class='licon blue' v-html="renderSvgIcon('meter')"></span> Water Meter </div>
            </b-col>
          </b-row>
        </div>
      </template>

      <template #modal-footer="{ ok, cancel, hide }">
        <base-button type="link" class="ml-auto" variant="outline-primary"
          @click="toggleLegendModal()">Close</base-button>
      </template>
    </b-modal>

    <div v-show='loading' class='mab_spinner light center large'></div>
  </div>
</template>
<script>
import MapboxGL from "mapbox-gl";
import MABMapStyle from '../../assets/mapstyles/mab_mapstyle.min.js';
import { MglMap, MglMarker, MglPopup } from "v-mapbox";
import FocusMenu from '../../custom_components/FocusMenu';
import BVIndicator from '../../custom_components/BVIndicator';
import SMStatusGraph from '../../custom_components/SMStatusGraph';
import SMStatusIndicator from '../../custom_components/SMStatusIndicator';
import SMLevelsIndicator from '../../custom_components/SMLevelsIndicator';
import SoilTempIndicator from '../../custom_components/SoilTempIndicator';
import MeterReading from '../../custom_components/MeterReading';
import EasingFunctions from "../../easingFunctions";
import mab_utils from '../../util/mab-utils';
import swal from 'sweetalert2';

export default {

  mixins: [mab_utils],

  components: {
    MglMap,
    MglMarker,
    MglPopup,
    FocusMenu,
    BVIndicator,
    SMStatusGraph,
    SMStatusIndicator,
    SMLevelsIndicator,
    SoilTempIndicator,
    MeterReading
  },

  data() {
    return {
      loading: false,
      startPos: [0, 0],
      startZoom: 3,
      mapboxObj: null,
      map: null,
      markers: [],
      markerScale: 0,
      toggleVisible: 0,
      visibleMarkers: 0,
      visiblePopups: [],
      fieldNameSearch: '',
      mapActions: null,
      mapStyle: '', /* was NULL */
      fieldsData: null,
      bounds: null,
      showLegendModal: false,
      refreshTimer: null,
      bInitialQuery: true,
      selectedNode: null,

      infoFilterType: ['sm_status', 'labels'],
      infoFilterTypes: [
        { text: 'S.M. Status', value: 'sm_status' },
        { text: 'S.M. Levels', value: 'sm_levels' },
        { text: 'Soil Temp', value: 'soil_temp' },
        { text: 'Rain Gauge', value: 'rain_gauge' },
        { text: 'Wells', value: 'wells' },
        { text: 'Weather S.', value: 'weather_s' },
        { text: 'Labels', value: 'labels' },
      ],

      nodeFilterType: ['sm', 'nut', 'well', 'meter'],
      nodeFilterTypes: [
        { text: 'S.M', value: 'sm' },
        { text: 'Nutrient', value: 'nut' },
        { text: 'Well', value: 'well' },
        { text: 'Meter', value: 'meter' },
      ]
    }
  },

  created() {

  },

  // #2 (created == #1)
  mounted() {
    this.mapStyle = MABMapStyle;
  },

  methods: {

    // #3
    mapLoaded(e) {
      e.map._isVue = true;
      this.map = e.map;
      MapboxGL._isVue = true;
      this.mapboxObj = MapboxGL;
      this.mapActions = e.component.actions;
      this.loadFieldsData();
    },

    // #4
    loadFieldsData(is_refresh = false) {
      this.loading = true;
      this.$axios.post("/api/map_data", {
        is_refresh: is_refresh
      }).then(resp => {
        this.loading = false;
        if (
          resp.data &&
          resp.data.nodes &&
          resp.data.nodes.length
        ) {
          this.fieldsData = resp.data.nodes;
          this.populateMarkers();
          this.calcBounds();
          this.setupMap();
          //this.resizeTheMap();

          // bInitialQuery is for the first Ajax Query.
          if (this.bInitialQuery) {
            if (this.$store.state.mapSettingsCache.visited) {
              this.focusMarkersFly();
            } else {
              this.focusMarkersEase();
            }
            this.bInitialQuery = false;
            //this.refreshTimer = setInterval(() => { this.loadFieldsData(true); }, 60000*5);
          } else {
            // setInterval data refresh
            this.doTextSearch();
          }
        } else {

          swal.fire({
            title: 'No nodes found',
            text: 'Please add one or more nodes.',
            showCancelButton: false,
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
              cancelButton: 'btn btn-outline-primary',
              confirmButton: 'btn btn-primary'
            }
          });

        }
      }).catch(err => { this.loading = false; });
    },

    setupMap() {
      // handle resize when Argon menu collapses via click
      document.querySelector('.sidenav-toggler').addEventListener('click', this.resizeTheMap);
      // initial marker scale
      this.scaleMarkers();
    },

    // named event handler: https://stackoverflow.com/questions/28056716/check-if-an-element-has-event-listener-on-it-no-jquery/47337711
    resizeTheMap() {
      setTimeout(() => {
        this.map.resize();
      }, 500);
    },

    scaleMarkers() {
      this.markerScale = this.map.getZoom() / 22;
    },

    saveMapSettings() {
      let settings = {
        'visited': true,
        'zoom': this.map.getZoom(),
        'center': this.map.getCenter(),
        'infoFilterType': this.infoFilterType,
        'nodeFilterType': this.nodeFilterType
      };
      this.$store.dispatch('save_map_settings', settings);
    },

    focusMarkersEase() {
      this.mapActions.easeTo({
        center: this.bounds.getCenter(),
        duration: 2000,
        animate: true,
        easing: EasingFunctions.easeInOutQuad,
        essential: true
      }).then(() => {
        if (this.$store.state.mapSettingsCache.infoFilterType && this.$store.state.mapSettingsCache.nodeFilterType) {
          this.infoFilterType = this.$store.state.mapSettingsCache.infoFilterType;
          this.nodeFilterType = this.$store.state.mapSettingsCache.nodeFilterType;
        }
        this.$refs.focusmenu.expandMenu();
        this.filterFields();
      });
    },

    focusMarkersFly() {
      this.mapActions.flyTo({
        zoom: this.$store.state.mapSettingsCache.zoom,
        center: this.$store.state.mapSettingsCache.center,
        duration: 2000,
        animate: true,
        easing: EasingFunctions.easeInOutQuad,
        essential: true
      }).then(() => {
        if (this.$store.state.mapSettingsCache.infoFilterType && this.$store.state.mapSettingsCache.nodeFilterType) {
          this.infoFilterType = this.$store.state.mapSettingsCache.infoFilterType;
          this.nodeFilterType = this.$store.state.mapSettingsCache.nodeFilterType;
        }
        this.$refs.focusmenu.expandMenu();
        this.filterFields();
      });
    },

    toggleMarkerPopup(marker) {
      if (typeof this.visiblePopups[marker.node_address] === 'undefined' || this.visiblePopups[marker.node_address] === false) {
        this.visiblePopups[marker.node_address] = true;
        this.selectedNode = marker.node_address;
      } else if (this.visiblePopups[marker.node_address] === true) {
        this.visiblePopups[marker.node_address] = false;
        this.selectedNode = null;
        setTimeout(() => {
          this.$refs[marker.node_address][0].marker._popup.remove(); // emits @close event
        }, 200);
      }
    },

    popupClosed() {
      this.selectedNode = null;
    },

    filterNodeType() {
      // reset text search when node filter type is changed
      this.fieldNameSearch = '';
      this.filterLabels();
      this.filterFields();
      //console.log(this.map.getStyle().layers);
    },

    filterLabels() {
      this.map.getStyle().layers.forEach((layer, idx) => {
        if (layer.id.includes("-label") || layer.id.includes("-shield")) {
          if (this.infoFilterType.includes('labels')) {
            this.map.setLayoutProperty(
              layer.id,
              'visibility',
              'visible'
            );
          } else {
            this.map.setLayoutProperty(
              layer.id,
              'visibility',
              'none'
            );
          }
        }
      });
    },

    // mapbox @onzoomend && @ondragend event
    filterFields(e) {
      // Filter Markers
      if (this.fieldNameSearch == '') {
        let _bounds = this.map.getBounds();
        this.visibleMarkers = 0;
        this.markers.forEach((m, idx) => {
          this.markers[idx].visible = this.nodeFilterType.includes(m.node_type) && _bounds.contains(m.position) ? true : false;
          if (this.markers[idx].visible) { this.visibleMarkers++; }
        });
      }
      // save zoom and center
      this.saveMapSettings();
      // reset scrollbar
      document.querySelector('.focus_menu .items_section').scrollTop = 0;
    },

    populateMarkers() {
      if (this.fieldsData.length) {

        this.markers = [];
        this.fieldsData.forEach((item, index) => {
          // ensure non-zero coords
          if (item.lng && item.latt) {
            // create markers array

            this.markers.push({
              'id': item.id,
              'link': '/soil_moisture/graph/' + item.node_address,
              'status': item.status,
              'full': item.full,
              'refill': item.refill,
              'date_time': item.date_time,
              'date_diff': item.date_diff,
              'node_id': item.node_id,
              'node_address': item.node_address,
              'base_node_address': item.base_node_address,
              'field_id': item.field_id,
              'field_name': item.field_name,
              'graph_type': item.graph_type,
              'graph_start_date': item.graph_start_date,
              'node_type': this.formatNodeType(item.node_type),
              'position': [parseFloat(item.lng), parseFloat(item.latt)],
              'latt': parseFloat(item.lng),
              'lng': parseFloat(item.latt),
              //4200 mV is full, 3000 mV is when it powers down
              'battLevel': Math.ceil((100 - ((item.bv) / (1200)))),
              // 'battVoltage': item.bv,
              'pump_status': false,
              'visible': true,
              'sm1': item.sm1,
              'sm2': item.sm2,
              'sm3': item.sm3,
              'sm4': item.sm4,
              'sm5': item.sm5,
              'sm6': item.sm6,
              'sm7': item.sm7,
              'sm8': item.sm8,
              'sm9': item.sm9,
              'sm10': item.sm10,
              'sm11': item.sm11,
              'sm12': item.sm12,
              'sm13': item.sm13,
              'sm14': item.sm14,
              'sm15': item.sm15,
              't1': item.t1,
              't2': item.t2,
              't3': item.t3,
              't4': item.t4,
              't5': item.t5,
              't6': item.t6,
              't7': item.t7,
              't8': item.t8,
              't9': item.t9,
              't10': item.t10,
              't11': item.t11,
              't12': item.t12,
              't13': item.t13,
              't14': item.t14,
              't15': item.t15,
              'measurement_type': item.measurement_type,
              'last_reading': item.pulse_1 ? item.pulse_1 : '00000000',
              'charging': item.charging,
              'nutrient_lower': 'nutrient_lower' in item ? item.nutrient_lower : null,
              'nutrient_upper': 'nutrient_upper' in item ? item.nutrient_upper : null,
              'nutrient_gauge': 'nutrient_gauge' in item ? item.nutrient_gauge : null,
              'nutrient_avg': 'nutrient_avg' in item ? item.nutrient_avg : null,

              'marker_outline_color': item.marker_outline_color
            });
          }
        });
      }
      //console.log(this.markers);
    },

    toggleBurger() {
      document.querySelector('.custom-nav-toggler').className = this.toggleVisible ? "custom-nav-toggler visible" : "custom-nav-toggler hidden";
      this.toggleVisible = !this.toggleVisible;
    },

    doTextSearch() {
      if (this.fieldNameSearch != '') {
        let resultCount = 0;
        this.markers.forEach((m, idx) => {
          // Search by field name OR
          // Search by Node Address
          // Should Respect Node Filter
          if ((this.nodeFilterType.includes(m.node_type)) && (
            (m.field_name.toLowerCase().indexOf(this.fieldNameSearch.toLowerCase()) > -1) ||
            (m.node_address.toLowerCase().indexOf(this.fieldNameSearch.toLowerCase()) > -1)
          )
          ) {
            this.markers[idx].visible = true;
            resultCount++;
          } else {
            this.markers[idx].visible = false;
          }
        });
        if (resultCount) {
          if (this.markers.length) {
            this.calcBounds();
            this.map.fitBounds(this.bounds, {
              linear: true,
              maxZoom: 13,
              duration: 2000,
              animate: true,
              padding: 100,
              easing: EasingFunctions.easeInOutQuad,
              essential: true
            });
          }
        }
      } else {
        this.showAllMarkers();
        this.filterFields();
      }
    },

    focusMarker(marker) {
      if (marker.node_address == this.selectedNode) {
        this.selectedNode = null;
      } else {
        this.selectedNode = marker.node_address;
      }
      // pan to marker
      this.mapActions.flyTo({
        center: marker.position,
        duration: 1000,
        animate: true,
        easing: EasingFunctions.easeInOutQuad,
        essential: true
      }).then(() => {
        this.$refs[marker.node_address][0].togglePopup();
      });
    },

    showAllMarkers() {
      this.markers.forEach((m, idx) => {
        this.markers[idx].visible = true;
      });
      this.visibleMarkers = this.markers.length;
    },

    calcBounds() {
      let _bounds = new this.mapboxObj.LngLatBounds();

      // Limit Bounds to United States
      let us_t = 49.3457868;   // north lat
      let us_b = 24.7433195;   // south lat
      let us_l = -124.7844079; // west long
      let us_r = -66.9513812;  // east long

      this.markers.forEach((m, idx) => {
        if (this.markers[idx].visible) {
          if (this.markers[idx].position[0] > us_l && this.markers[idx].position[0] < us_r &&
            this.markers[idx].position[1] > us_b && this.markers[idx].position[1] < us_t) {
            _bounds.extend(this.markers[idx].position);
          }
          //_bounds.extend(this.markers[idx].position);
        }
      });
      this.bounds = _bounds;
    },

    /* TODO: Add to Utils Mixin */
    calcMarkerIconColor(marker, reading, type) {
      if (type == 'sm') {
        let color = 'white';
        if (reading >= 0 && reading < 25) { color = "red"; }
        else if (reading >= 25 && reading < 50) { color = "orange"; }
        else if (reading >= 50 && reading < 75) { color = "green"; }
        else if (reading >= 75 && reading <= 100) { color = "blue"; }
        else { color = "black"; }
        if (marker.date_diff.days >= 3) { color = "black"; }
        return color;
      } else if (type == 'well') {
        return "red";
      } else if (type == 'meter') {
        return "blue";
      } else {
        return "black";
      }
    },

    calcMarkerBorderColor(marker) {
      return "border: 2px solid " + marker.marker_outline_color;
    },

    formatNodeType(type) {
      if (type == 'Soil Moisture') return 'sm';
      if (type == 'Nutrients') return 'nut';
      if (type == 'Wells') return 'well';
      if (type == 'Water Meter') return 'meter';
      return 'sm';
    },

    formatUOM(value) {
      if (value == 1) { return 'cm3'; }
      if (value == 2) { return 'gal'; }
      return '';
    },

    togglePump(marker) {
      this.loading = true;
      if (marker.node_address) {
        this.$axios.get("/api/toggleWM/" + marker.node_address)
          .then((resp) => {
            this.loading = false;
            this.$notify({
              title: 'Status',
              message: 'Pump Toggled',
              type: 'info',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          });
      }
    },

    toggleLegendModal() {
      this.showLegendModal = !this.showLegendModal;
    },

    renderSvgIcon(type) {
      if (type == 'well') {
        return '<svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 165 187.5"><path stroke-width="0" d="M.001 187.5v-7.499h7.484v-52.499h7.482v-7.501h7.482V67.5H7.484v-7.499h14.965V39.002L7.484 45.001H0V30h7.484v.001l74.829-30 74.83 30h7.483v15.001h-7.484l-.001-.001-14.964-5.998.001 20.998h7.857v-.002h7.482V75H165v7.499h-7.483v.001h-7.482v-15h-7.857v52.501h7.482v7.501h7.483v52.499h7.482v7.499H.001zm119.728-59.998v-7.501h7.482V67.5h-14.967v7.501h-26.04v15h18.558l-7.484 30h-29.93l-7.483-30h18.857v-15h-26.34V67.5H37.416v52.501h7.482v7.501h74.831zM52.382 60.001v-7.499h59.862v7.499h14.967V33.002L82.313 15.001l-44.897 18v27h14.966z" fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16"/></svg>';
      }
      if (type == 'sm') {
        // return '<svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5"><path stroke-width="0" d="M37.499 187.5l-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15z" fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16"/></svg>';
        return '<svg stroke-width=".501" stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5"><g stroke-linecap="round" stroke="none" fill="none" font-size="16"><path d="m37.499 187.5-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15Z" fill="currentColor"/><path d="m7.177 124.653 15.222-1.48c.916 5.109 2.776 8.861 5.576 11.258 2.801 2.396 6.579 3.595 11.338 3.595 5.038 0 8.835-1.066 11.39-3.198 2.554-2.132 3.83-4.625 3.832-7.48-.002-1.831-.538-3.39-1.613-4.677-1.074-1.285-2.95-2.404-5.629-3.355-1.831-.635-6.008-1.762-12.527-3.383-8.386-2.079-14.27-4.635-17.653-7.665-4.757-4.263-7.135-9.461-7.135-15.592 0-3.946 1.119-7.637 3.356-11.072 2.237-3.437 5.462-6.053 9.673-7.85 4.211-1.796 9.294-2.695 15.247-2.695 9.726 0 17.046 2.132 21.962 6.395 4.915 4.264 7.496 9.954 7.743 17.072l-15.645.687c-.669-3.981-2.104-6.844-4.307-8.588-2.202-1.744-5.506-2.617-9.911-2.617-4.544 0-8.104.935-10.677 2.802-1.657 1.198-2.483 2.801-2.483 4.81 0 1.832.774 3.4 2.325 4.704 1.973 1.655 6.766 3.381 14.376 5.179 7.612 1.797 13.241 3.656 16.888 5.576 3.647 1.922 6.501 4.547 8.562 7.875 2.061 3.33 3.092 7.444 3.092 12.342 0 4.439-1.232 8.598-3.699 12.474-2.466 3.877-5.956 6.757-10.466 8.641-4.51 1.885-10.13 2.828-16.861 2.828-9.796 0-17.319-2.264-22.569-6.792-5.25-4.527-8.386-11.126-9.407-19.794Z" fill-rule="nonzero" stroke-linejoin="round" stroke-width=".375" fill="#fff" stroke-miterlimit="79.84"/></g></svg>';
      }
      if (type == 'nut') {
        // return '<svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5"><path stroke-width="0" d="M37.499 187.5l-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15z" fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16"/></svg>';
        return '<svg stroke-width=".501" stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 90 187.5"><g stroke-linecap="round" stroke="none" fill="none" font-size="16"><path d="m37.499 187.5-15-15V45l-7.5-7.5V7.499H0V0h74.999v7.499H59.998v7.502h22.5v-7.5h7.501V37.5h-7.501V30h-22.5l.001 7.5-7.5 7.5v127.5l-15 15Z" fill="currentColor"/><path d="M9.001 147.09V75.155h14.132l29.441 48.039V75.155h13.493v71.935H51.494l-28.999-46.909v46.909H9.001Z" fill-rule="nonzero" stroke-linejoin="round" stroke-width=".375" fill="#fff" stroke-miterlimit="79.84"/></g></svg>';
      }
      if (type == 'meter') {
        return '<svg stroke-linejoin="bevel" fill-rule="evenodd" xmlns="http://www.w3.org/2000/svg" overflow="visible" width="100%" height="100%" viewBox="0 0 225 150"><path stroke-width="0" d="M43.746 105.001H22.499V120H0V29.999h22.499V45h21.246C55.326 18.519 81.762 0 112.498 0c30.735 0 57.171 18.519 68.752 45h21.249V29.999h22.499V120h-22.499v-14.999h-21.25C169.669 131.482 143.233 150 112.498 150c-30.736 0-57.172-18.518-68.752-44.999zM179.998 75c0-37.26-30.24-67.5-67.5-67.5-37.26 0-67.5 30.24-67.5 67.5 0 37.26 30.24 67.5 67.5 67.5 37.26 0 67.5-30.24 67.5-67.5zm-127.5 0c0-33.12 26.88-60 60-60 33.12 0 60 26.88 60 60 0 33.12-26.88 60-60 60-33.12 0-60-26.88-60-60zm37.5 22.5V82.501H74.999V97.5h14.999zm30-.001V82.5h-14.999v14.999h14.999zm30 0V82.5h-14.999v14.999h14.999zm.001-30V52.5H74.998v14.999h75.001z" fill="currentColor" stroke="white" font-family="Times New Roman" font-size="16"/></svg>';
      }
    },

    goToGraph(marker) {
      if (this.userCan('Graph', 'Soil Moisture', marker.node_id, 'O')) {
        this.$router.push({
          name: "soil_moisture_graph",
          params: { node_address: marker.node_address }
        });
      }
    }
  },

  beforeRouteLeave(to, from, next) {
    // if(this.refreshTimer){
    //   console.log("Clearing Map Timer");
    //   clearInterval(this.refreshTimer);
    // }
    next();
  }
};

</script>
<style scoped>
.mapboxgl-canvas {
  width: 100%;
  height: auto;
}

.map_container {
  position: relative;
  width: 100%;
  height: 100vh;
}

/* Field Marker Styling */

.field_marker:hover {
  cursor: pointer;
}

/* Focus menu */

/* Focus Menu - Search Field */

.fields_search {
  padding: 0.5rem;
  height: 2rem;
  /*margin: 0.5rem;*/
  width: calc(100% - 1rem);
}

.fields_search::placeholder,
.fields_search::placeholder-shown {
  color: #1CA554;
}

/* Focus Menu - Search Field - Clear Button */

.fields_search_clear_btn {
  position: absolute;
  width: 1rem;
  height: 1rem;
  border-radius: 0.1rem;
  right: 0.5rem;
  /* was 1.0 rem */
  top: 50%;
  transform: translateY(-50%);
  text-align: center;
  font-size: 1rem;
  line-height: 1;
  background-color: #00A04C;
  color: white;
  cursor: pointer;
}

.fields_search_clear_btn:after {
  content: '+';
  display: block;
  font-weight: bold;
  transform: rotate(45deg);
}

/* Focus Menu - Field Records */

.field_record {
  position: relative;
  padding: 0.5rem;
  padding-right: 4rem;
  margin-right: 0.6rem;
  margin-top: 1px;
  min-height: 6rem;
  font-size: 0.8rem;
  line-height: 1.2;
  color: black;
  border-bottom: 1px solid #eee;
}

.field_record:hover {
  cursor: pointer;
}

.field_record .field_name {
  font-weight: bold;
}

.field_record .indicators {
  position: absolute;
  top: 0;
  right: 0;
  height: 100%;
  width: 3.5rem;
  margin: 0 0.5rem;
  display: flex;
  flex-flow: row;
  align-items: center;
  justify-content: flex-end;
}

.field_record .indicators>div {
  display: block;
  margin: 0 0.25rem;
}

.field_record_selected {
  /*background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);*/
  outline: 1px solid #01A04D;
  margin-left: 0.6rem;
  transition: all .25s ease-in-out;
}

.ps__rail-y {
  z-index: 99;
}

/* Top right Filter Menu */

.info_filter_menu {
  position: absolute;
  display: block;
  top: 1rem;
  right: 1rem;
  z-index: 99;
}

.info_filter_menu .btn:hover {
  cursor: pointer;
}

/* Side Menu Node Type Filter Menu */
.type_filter_menu {
  width: 100%;
  padding: 0 0.5rem;
}

/* Selected Node */

.selected_node {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 0.25em;
}

/* Dialog Styling */

.marker_info_wrap {
  display: flex;
  justify-content: center;
  flex-flow: column;
  align-items: center;
  font-size: 0.9rem;
}

.marker_info_wrap .field_name {
  padding: 0.25rem 0.5rem;
  color: white;
  font-weight: bold;
  font-size: 1.1rem;
  font-family: 'Open Sans', sans-serif;
  text-shadow: 0 0 5px black;
  text-align: center;
}

.marker_info_wrap .node_address,
.marker_info_wrap .last_reading_date {
  display: block;
  /*font-family: 'Roboto Mono', monospace;*/
  font-family: 'Open Sans', sans-serif;
  padding: 0 0.25rem;
  color: white;
  width: 100%;
  text-align: center;
}

.marker_info_wrap .pump_control .pump_control_inner {
  display: flex;
  flex-flow: column;
  width: 100%;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  color: white;
  box-shadow: inset 0 0 10px #000;
  background: #222;
  border-radius: 0.25rem;
  border: 1px solid #333;
}

.marker_info_wrap .meter_control {
  display: flex;
  flex-flow: column;
  width: 100%;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
}

.marker_info_wrap .sm_control {
  display: flex;
  flex-flow: row wrap;
  /* NEW */
  justify-content: center;
  align-items: flex-start;
  /* NEW */
  /*margin:0.25rem 0;*/
  margin: 0;
}

.marker_info_wrap .sm_control .sm_control_item {
  display: flex;
  flex-flow: column;
  align-items: center;
  flex: 1 0 33.3333%;
  /* NEW */
}

@media(max-width:1023px) {
  .marker_info_wrap .sm_control .sm_control_item {
    flex: 1 0 50%;
    /* NEW */
  }
}

.marker_info_wrap .sm_control .sm_control_item .marker_info_horz {
  display: flex;
  flex-flow: row;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
  margin: 0.75rem 0;
}

.marker_info_wrap .sm_control .sm_control_item .marker_info_horz>* {
  margin: 0 0.25rem;
}

.search_wrapper {
  position: relative;
  margin: 0.5rem;
}

.clearfix::after {
  content: "";
  clear: both;
  display: table;
}

.legend_btn {
  position: absolute;
  bottom: 2rem;
  right: 0.5rem;
}

.legend_btn .circle {
  display: inline-block;
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 1.25rem;
  background: #eee;
  color: #111;
  font-weight: bold;
}

.marker {
  width: 50px;
  height: 50px;
  border: 2px solid white;
  padding: 0.4em;
  border-radius: 100%;
}

.marker:hover {
  cursor: pointer;
}

.legend_modal .legend_box .licon {
  width: 1.5rem;
  height: 1.5rem;
  margin-right: 0.5rem;
}

.legend_modal .legend_box .blue {
  color: blue;
}

.legend_modal .legend_box .red {
  color: red;
}

.legend_modal .legend_box .yellow {
  color: yellow;
}

.legend_modal .legend_box .orange {
  color: orange;
}

.legend_modal .legend_box .green {
  color: green;
}
</style>

