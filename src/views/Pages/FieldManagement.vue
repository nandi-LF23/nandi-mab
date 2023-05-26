<template>
  <div class='map_container fieldmap'>
    <FocusMenu @focusMenuChanged="toggleBurger" ref='focusmenu'>

      <template #header>
        <div class='search_wrapper clearfix'>
          <b-form-input :disabled="!visibleMarkers" v-model="fieldNameSearch" @input="doTextSearch"
            class="form-control-flush fields_search" placeholder="Search...">
          </b-form-input>
          <div v-show="fieldNameSearch" class='fields_search_clear_btn' @click="fieldNameSearch = ''; filterFields();">
          </div>
        </div>

        <b-form-group class='type_filter_menu'>
          <b-form-radio-group v-model="nodeFilterType" :options="nodeFilterTypes" size="sm" style="width:100%" buttons
            button-variant="primary" @change="filterNodeType" v-on:click.stop.prevent="() => { }">
          </b-form-radio-group>
        </b-form-group>
      </template>

      <template v-for="(     marker, index     ) in      markers     ">
        <div :key="nodeFilterType + index" v-if="marker.visible" class='field_record' @click=" showFieldModal(marker.node_address)">
          <div class='field_name'>{{ marker.field_name }}</div>
          <div class='node_address'>{{ marker.node_address }}</div>
          <div class='last_reading_date' :style="'color:' + calcLastReadingColor(marker)">{{ marker.date_time }}</div>
          <div v-if="marker.node_type == 'sm' && marker.status" class='field_sm_status'>Status: {{ marker.status }}%
          </div>
          <div class='indicators'>
            <SMStatusGraph v-if="marker.node_type == 'sm' && marker.status" :full="marker.full" :status="marker.status"
              :refill="marker.refill" :size="'35%'"
              :tooltip="'F: ' + marker.full + '%, S: ' + marker.status + '%, R: ' + marker.refill + '%'" :label="'S.M'"
              @clicked=" goToGraph(marker)" />
            <div v-else style="width:35%"></div>
            <BVIndicator :level="marker.battLevel" :size="'35%'" :charging="marker.charging"
              :tooltip="'Level: ' + marker.battLevel + '%'" />
          </div>
        </div>
      </template>
    </FocusMenu>

    <b-modal dialog-class='mab_modal' v-model="fieldModalVisible" centered hide-backdrop no-enforce-focus size='lg'>

      <template #modal-header="{ close }">
        <h6 class="modal-title">{{ field_model.field_name }}</h6>
        <!-- <span>{{ field_model.temp_avg }}</span>
          <span>{{ field_model. }}</span> -->
        <div :style="'color:' + calcLastReadingColor(field_model)">{{ field_model.date_time }}</div>
        <span>{{ field_model.node_address }}</span>

      </template>

      <template #default="{ hide }">
        <b-row>
          <b-col lg="4" md="6">
            <div v-if="nodeFilterType == 'nut' && field_model.node_type == 'nut'">
              <!-- <div class='text-center'>N03 PPM Avg</div> -->
              <highcharts class="hc" :options="chartOptionsN03PPM" :update-args="chartUpdateArgs" ref="hchart">
              </highcharts>
            </div>

            <div v-if="nodeFilterType == 'sm' && (field_model.node_type == 'sm' || field_model.node_type == 'nut')">
              <div class='text-center'>S.M</div>
              <!-- the gauge. -->
              <highcharts class="hc" :options="chartOptionsSM" :update-args="chartUpdateArgs" ref="hchart">
              </highcharts>
              <span class="status-text">Status: {{ (field_model.status) }}%</span>
            </div>
          </b-col>

          <b-col lg="4" sm="6">
            <div v-if="nodeFilterType == 'nut' && field_model.node_type == 'nut'">
              <!-- <div class='text-center'>NH4 PPM Avg</div> -->
              <highcharts class="hc" :options="chartOptionsNH4PPM" :update-args="chartUpdateArgs" ref="hchart">
              </highcharts>
            </div>
            <div v-else-if="nodeFilterType == 'sm' && (field_model.node_type == 'sm' || field_model.node_type == 'nut')">
              <div class='text-center'>Temp</div>
              <highcharts class="hc" :options="chartOptionsTemp" :update-args="chartUpdateArgs" ref="hchart">
              </highcharts>
            </div>
          </b-col>

          <b-col class="avg-styles" lg="4" md="6">
            <ul v-if="nodeFilterType == 'nut' && field_model.node_type == 'nut'">
              <li>SM Avg: {{ (field_model.sm_avg) }}%</li>
              <li>Temp Avg: {{ (field_model.temp_avg) + field_model.temp_uom }}</li>
            </ul>
          </b-col>

          <!-- <b-col lg="4" md="6">
            <ul v-if=" active_zone ">
              <li v-for="(   val, key   ) in    active_zone.data   "><strong>{{key}}</strong>: {{val}}</li>
            </ul>
          </b-col> -->

        </b-row>
      </template>

      <template #modal-footer="{ ok, cancel, hide }">
        <b-button variant="outline-primary" class="ml-auto" @click="closeFieldModal">
          Close
        </b-button>
      </template>
    </b-modal>

    <MglMap :repaint="true" :mapboxGl="mapboxObj" :accessToken="$store.state.mapBoxAccessToken" :mapStyle.sync="mapStyle"
      :attributionControl="false" :center="startPos" logoPosition="bottom-right" @load="mapLoaded" @zoom="scaleMarkers"
      @zoomend="filterFields" @dragend="filterFields">

      <template v-for="(     marker, idx     ) in      markers     ">

        <template v-if="marker.zones && marker.zones.length">
          <MglGeojsonLayer v-for="(     zone, idx2     ) in      marker.zones     " :key="idx + '_' + idx2"
            :sourceId="zone.layer.id" :layerId="zone.layer.id" :source="zone.source" :layer="zone.layer"
            @click=" setFieldModalZoneInfo(marker.node_address, zone)" :clearSource="false" />
        </template>

        <MglGeojsonLayer v-if="marker.perimeter" :sourceId="marker.node_address" :layerId="marker.node_address"
          :source="marker.perimeter" :layer="marker.layer" :clearSource="false"
          @click=" showFieldModal(marker.node_address)" @mouseover="showLayerCursor" @mouseout="hideLayerCursor" />

        <MglMarker :key="marker.node_address + '_' + idx" :ref="marker.node_address"
          @click=" !marker.perimeter ? showFieldModal(marker.node_address) : {}" :coordinates="marker.position"
          anchor="top">

          <div class='field_marker' slot="marker">
            <div :class="[
              'marker',
              mapLayerType.includes('nodes') && marker.visible ? 'marker_visible' : 'marker_hidden',
              marker.perimeter ? 'has_perimeter' : 'no_perimeter'
            ]
              "
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

        </MglMarker>

      </template>
    </MglMap>

    <b-form-group class='info_filter_menu'>
      <b-form-checkbox-group v-model="mapLayerType" :options="mapLayerTypes" stacked buttons button-variant="primary"
        size="sm" @change="filterNodeType" v-on:click.stop.prevent="() => { }">
      </b-form-checkbox-group>
    </b-form-group>

    <base-button icon type="primary" class='legend_btn' size="sm" @click=' toggleLegendModal()'><span
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
            <b-col md>
              <h3>Meters</h3>
              <div class='line'><span class='licon blue' v-html="renderSvgIcon('meter')"></span> Water Meter </div>
            </b-col>
          </b-row>
          <b-row>
            <b-col md>
              <h3>Field Zones</h3>
            </b-col>
          </b-row>
          <b-row>
            <b-col md>
              <div class='line'><span class='licon' v-html="renderSvgIcon('green_zone')"></span> Zone 1 </div>
              <div class='line'><span class='licon' v-html="renderSvgIcon('lightgreen_zone')"></span> Zone 2 </div>
            </b-col>
            <b-col md>
              <div class='line'><span class='licon' v-html="renderSvgIcon('yellow_zone')"></span> Zone 3 </div>
              <div class='line'><span class='licon' v-html="renderSvgIcon('red_zone')"></span> Zone 4 </div>
            </b-col>
          </b-row>
        </div>
      </template>

      <template #modal-footer="{ ok, cancel, hide }">
        <base-button type="link" class="ml-auto" variant="outline-primary"
          @click=" toggleLegendModal()">Close</base-button>
      </template>
    </b-modal>

    <div v-show='loading' class='mab_spinner light center large'></div>
  </div>
</template>
<script>
import MapboxGL from "mapbox-gl";
import MABMapStyle from '../../assets/mapstyles/mab_mapstyle.min.js';
import { MglMap, MglMarker, MglPopup, MglGeojsonLayer } from "v-mapbox";
import FocusMenu from '../../custom_components/FocusMenu';
import BVIndicator from '../../custom_components/BVIndicator';
import SMStatusGraph from '../../custom_components/SMStatusGraph';
import SMStatusIndicator from '../../custom_components/SMStatusIndicator';
import SMLevelsIndicator from '../../custom_components/SMLevelsIndicator';
import SoilTempIndicator from '../../custom_components/SoilTempIndicator';
import MeterReading from '../../custom_components/MeterReading';
import NiftyGauge from '../../custom_components/NiftyGauge';
import EasingFunctions from "../../easingFunctions";
import mab_utils from '../../util/mab-utils';
import swal from 'sweetalert2';
import Highcharts from 'highcharts';

export default {

  mixins: [mab_utils],

  components: {
    MglMap,
    MglMarker,
    MglPopup,
    MglGeojsonLayer,
    FocusMenu,
    BVIndicator,
    SMStatusGraph,
    SMStatusIndicator,
    SMLevelsIndicator,
    SoilTempIndicator,
    MeterReading,
    NiftyGauge
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

      fieldModalVisible: false,

      empty_field_model: {
        field_name: '',
        node_address: '',
        node_type: '',
        status: '',
        temp_avg: ''
      },

      field_model: {
        field_name: '',
        node_address: '',
        node_type: '',
        status: '',
        sm_avg: '',
        temp_avg: '',
        NH4_avg: '',
        N03_avg: '',
        temp_uom: '',
        full: 0,
        refill: 0,
        upper_value: 0,
        lower_value: 0,
      },

      bv: 0,
      bp: 0,
      graph_type: '',
      series: [],
      chartUpdateArgs: [true, true, {
        duration: 1000
      }],
      plotOptions: {
        series: {
          states: {
            inactive: {
              opacity: 1
            }
          }
        }
      },
      exporting: {
        enabled: false
      },
      plotLines: [],
      resetButton: {
        text: "Reset",
        enabled: true,
        // onclick: (e) => {
        //   this.resetSeriesData();
        // }
      },

      active_zone: null,

      mapLayerType: ['zones', 'fields', 'nodes', 'labels'],
      mapLayerTypes: [
        { text: 'Zones', value: 'zones' },
        { text: 'Fields', value: 'fields' },
        { text: 'Nodes', value: 'nodes' },
        { text: 'Labels', value: 'labels' },
      ],

      nodeFilterType: 'sm',
      nodeFilterTypes: [
        { text: 'S.M', value: 'sm' },
        { text: 'Nutrient', value: 'nut' },
        { text: 'Well', value: 'well' },
        { text: 'Meter', value: 'meter' },
      ]
    }
  },

  computed: {
    chartOptionsSM() {
      var fields_smAvg = parseFloat(this.field_model.sm_avg);

      var refill1 = parseFloat(this.field_model.refill);
      var full1 = parseFloat(this.field_model.full);
      var upper_value1 = parseFloat(this.field_model.upper_value);
      var lower_value1 = parseFloat(this.field_model.lower_value);

      var status1 = this.field_model.status;

      var x = (lower_value1 * 30) / 100;
      var xx = parseFloat(lower_value1 - x);

      var y = (upper_value1 * 30) / 100;
      var yy = parseFloat(upper_value1 + y);

      // console.log('xx: ' + xx);
      // console.log('refill: ' + refill1);
      // console.log('lower_val: ' + lower_value1);

      // console.log('yy: ' + yy);
      // console.log('full: ' + full1);
      // console.log('upper_val: ' + upper_value1);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        credits: {
          enabled: false
        },

        title: {
          text: ''
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: null,
          center: ['50%', '50%'],
          size: '80%'
        },

        yAxis: [{
          min: xx,
          max: yy,
          tickPixelInterval: 10,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 50,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 10,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: xx,
            to: refill1,
            color: '#DF5353', // red
            thickness: 6
          }, {
            from: refill1,
            to: full1,
            color: '#55BF3B', // yellow
            thickness: 6
          }, {
            from: full1,
            to: yy,
            color: 'blue', // green
            thickness: 6
          }]
        },

        {
          min: xx,
          max: yy,
          //tickPixelInterval: 30,
          tickPosition: 'inside',
          tickColor: 'transparent',
          tickLength: 10,
          tickWidth: 5,
          minorTickInterval: null,
          labels: {
            //distance: 10,
            enabled: false
          },
          plotBands: [{
            from: full1 - 0.5,
            to: full1,
            color: 'black', // blue
            thickness: 6
          }]
        },
        {
          min: xx,
          max: yy,
          //tickPixelInterval: 30,
          tickPosition: 'inside',
          tickColor: 'transparent',
          tickLength: 10,
          tickWidth: 5,
          minorTickInterval: null,
          labels: {
            enabled: false
          },
          plotBands: [{
            from: refill1 - 0.5,
            to: refill1,
            color: 'black', // maroon
            thickness: 6
          }]
        },
        ],

        series: [{
          name: 'Average',
          data: [fields_smAvg],
          dataLabels: {
            format: 'S.M Avg {y}%',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: "100%",
            rearLength: "-25%",
            backgroundColor: 'black',
            // baseWidth: 12,
            baseLength: '50%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    chartOptionsTemp() {

      var smTempuom = this.field_model.temp_uom;

      var tt = smTempuom != '0' ? smTempuom : '°';

      // var smTemp = parseFloat(this.soil_temp.avg_M1);
      // smTemp = parseFloat(smTemp.toFixed(2));

      var fields_smTemp = this.field_model.temp_avg;
      fields_smTemp = parseFloat(fields_smTemp);
      console.log(this.field_model.temp_gauge);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        credits: {
          enabled: false
        },

        title: {
          text: ''
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: null,
          center: ['50%', '50%'],
          size: '80%',
        },

        yAxis: {
          min: 0,
          max: 100,
          tickPixelInterval: 30,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 50,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 10,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: 0,
            to: 40,
            color: '#DF5353', // green
            thickness: 6
          }, {
            from: 40,
            to: 70,
            color: '#DDDF0D', // yellow
            thickness: 6
          }, {
            from: 70,
            to: 100,
            color: '#55BF3B', // red
            thickness: 6
          }]
        },

        series: [{
          name: 'Temperature',
          data: [fields_smTemp],
          dataLabels: {
            format: 'Temp Avg {y}' + tt,
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: "100%",
            rearLength: "-25%",
            backgroundColor: 'black',
            // baseWidth: 12,
            baseLength: '50%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    chartOptionsN03PPM() {
      var fields_N03 = this.field_model.NO3_avg;

      fields_N03 = parseFloat(fields_N03.toFixed(2));

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        credits: {
          enabled: false
        },

        title: {
          text: ''
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: null,
          center: ['50%', '75%'],
          size: '100%',
        },

        yAxis: {
          min: 0,
          max: 100,
          tickPixelInterval: 30,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 50,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 10,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: 0,
            to: 40,
            color: '#DF5353', // green
            thickness: 6
          }, {
            from: 40,
            to: 70,
            color: '#DDDF0D', // yellow
            thickness: 6
          }, {
            from: 70,
            to: 100,
            color: '#55BF3B', // red
            thickness: 6
          }]
        },

        series: [{
          name: 'N03',
          data: [fields_N03],
          dataLabels: {
            format: 'N03 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: "100%",
            rearLength: "-25%",
            backgroundColor: 'black',
            // baseWidth: 12,
            baseLength: '50%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    chartOptionsNH4PPM() {
      var fields_NH4 = this.field_model.NH4_avg;
      fields_NH4 = parseFloat(fields_NH4.toFixed(2));

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        credits: {
          enabled: false
        },

        title: {
          text: ''
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: null,
          center: ['50%', '75%'],
          size: '100%',
        },

        yAxis: {
          min: 0,
          max: 100,
          tickPixelInterval: 30,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 50,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 10,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: 0,
            to: 40,
            color: '#DF5353', // green
            thickness: 6
          }, {
            from: 40,
            to: 70,
            color: '#DDDF0D', // yellow
            thickness: 6
          }, {
            from: 70,
            to: 100,
            color: '#55BF3B', // red
            thickness: 6
          }]
        },

        series: [{
          name: 'NH4',
          data: [fields_NH4],
          dataLabels: {
            format: 'NH4 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: "100%",
            rearLength: "-25%",
            backgroundColor: 'black',
            // baseWidth: 12,
            baseLength: '50%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

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
      this.loadFieldMapData();
    },

    // #4
    loadFieldMapData(is_refresh = false) {
      this.loading = true;
      this.$axios.post("/api/fieldmap_data", {
        is_refresh: is_refresh
      }).then(resp => {
        this.loading = false;
        if (
          resp.data &&
          resp.data.nodes &&
          resp.data.nodes.length
        ) {

          this.fieldsData = resp.data.nodes;
          this.field_model.node_address = resp.data.nodes;

          this.populateMarkers();
          this.calcBounds();
          this.setupMap();

          // bInitialQuery is for the first Ajax Query.
          if (this.bInitialQuery) {
            if (this.$store.state.mapSettingsCache.visited) {
              this.focusMarkersFly();
            } else {
              this.focusMarkersEase();
            }
            this.bInitialQuery = false;
            //this.refreshTimer = setInterval(() => { this.loadFieldMapData(true); }, 30000); // 60000*5
          } else {
            // setInterval data refresh
            this.doTextSearch();
            this.filterNodeType();
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

    loadGraphData() {

      this.loading = true;
      this.in_progress = true;

      this.$refs.hchart.chart.showLoading(this.bInitialQuery ? "Loading..." : "Refreshing...");

      return this.$axios.post("/api/Graph", {
        node_address: this.current_node,
        graph_type: this.graph_type,
        graph_start_date: this.graph_start_date,
        bv: this.bv,
        bp: this.bp
      }).then(resp => {

        this.loading = false;
        this.in_progress = true;
        this.$refs.hchart.chart.hideLoading();


        if (resp.data) {

          if (resp.data.message && resp.data.message == 'no_data') {
            swal.fire({
              title: 'No data found',
              text: "This node currently has no data to plot.",
              showCancelButton: false,
              confirmButtonText: 'OK',
              buttonsStyling: false,
              customClass: {
                cancelButton: 'btn btn-outline-primary',
                confirmButton: 'btn btn-primary'
              }
            });
            this.chartTitle.text = "No data found";
            //setTimeout(() => { this.in_progress = false; }, 1);
            return;
          }

          this.selection_start = null;
          this.selection_end = null;

          // COMMON DATA
          this.node_id = resp.data.node_id;
          this.field_name = resp.data.field_name;
          //this.field_model.sm_avg = resp.field_model.sm_avg;
          // Experimental
          if (this.bInitialQuery) {
            this.graph_start_date = resp.data.graph_start_date;
            this.graph_type = resp.data.graph_type;
            this.sub_days = resp.data.sub_days;
          }
          this.plotOptions = resp.data.graph.plotOptions;
          this.chartTitle = resp.data.graph.title;

          this.series = resp.data.graph.series.filter((series) => { if (series.data) { return series.data.length > 0; } else return false; });
        }
        setTimeout(() => { this.in_progress = false; this.bInitialQuery = false; }, 1);
        return resp;
      });
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
        'nodeFilterType': this.nodeFilterType,
        'mapLayerType': this.mapLayerType
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
        if (this.$store.state.mapSettingsCache.mapLayerType && this.$store.state.mapSettingsCache.nodeFilterType) {
          this.mapLayerType = this.$store.state.mapSettingsCache.mapLayerType;
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
        if (this.$store.state.mapSettingsCache.nodeFilterType) {
          this.nodeFilterType = this.$store.state.mapSettingsCache.nodeFilterType;
        }
        this.$refs.focusmenu.expandMenu();
        this.filterFields();
      });
    },

    filterNodeType() {
      // reset text search when node filter type is changed
      this.fieldNameSearch = '';
      this.filterLabels();
      this.filterFields();
      this.filterLayers();
      //console.log(this.map.getStyle().layers);
    },

    filterLabels() {
      this.map.getStyle().layers.forEach((layer, idx) => {
        if (layer.id.includes("-label") || layer.id.includes("-shield")) {
          if (this.mapLayerType.includes('labels')) {
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
    filterFields() {
      // Filter Markers
      if (this.fieldNameSearch == '') {
        let _bounds = this.map.getBounds();
        this.visibleMarkers = 0;
        this.markers.forEach((m, idx) => {
          // toggle marker visibility
          this.markers[idx].visible = (this.nodeFilterType == m.node_type && _bounds.contains(m.position)) ? true : false;
          if (this.markers[idx].visible) { this.visibleMarkers++; }
        });
      }

      // save zoom and center
      this.saveMapSettings();
      // reset scrollbar
      document.querySelector('.focus_menu .items_section').scrollTop = 0;
    },

    filterLayers() {
      this.markers.forEach((m, idx) => {

        // When Node Filter Button was Clicked

        if (this.mapLayerType.includes('fields')) {

          if (this.nodeFilterType == 'sm') {
            // toggle layer visibility
            this.markers[idx].layer.filter = ['all', ['==', ['get', 'Field'], true], ['==', ['get', 'Soil Moisture'], true]];
            // toggle layer color
            if (this.markers[idx].color_sm_status) {
              this.markers[idx].layer.paint['fill-color'] = this.markers[idx].color_sm_status;
              this.markers[idx].layer.paint['fill-outline-color'] = this.markers[idx].color_sm_status;
            }
          }

          if (this.nodeFilterType == 'nut') {
            // toggle layer visibility
            this.markers[idx].layer.filter = ['all', ['==', ['get', 'Field'], true], ['==', ['get', 'Nutrients'], true]];
            // toggle layer color
            if (this.markers[idx].color_ppm_avg) {
              this.markers[idx].layer.paint['fill-color'] = this.markers[idx].color_ppm_avg;
              this.markers[idx].layer.paint['fill-outline-color'] = this.markers[idx].color_ppm_avg;
            }
          }

        } else {
          this.markers[idx].layer.filter = ['==', ['get', 'Field'], false];
        }

        if (this.nodeFilterType == 'well') {
          // toggle layer visibility
          this.markers[idx].layer.filter = ['==', ['get', 'Well Controls'], true];
        }

        if (this.nodeFilterType == 'meter') {
          // toggle layer visibility
          this.markers[idx].layer.filter = ['==', ['get', 'Water Meter'], true];
        }

        if (this.markers[idx].zones) {
          this.markers[idx].zones.forEach((z, idx2) => {
            if (this.mapLayerType.includes('zones')) {
              this.markers[idx].zones[idx2].layer.filter = ['==', ['get', 'Zone'], true];
            } else {
              this.markers[idx].zones[idx2].layer.filter = ['==', ['get', 'Zone'], false];
            }
          });
        }

      });
    },

    doTextSearch() {
      if (this.fieldNameSearch != '') {
        let resultCount = 0;
        this.markers.forEach((m, idx) => {
          // Search by field name OR
          // Search by Node Address
          // Should Respect Node Filter
          if ((m.node_type == this.nodeFilterType) && (
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
              'visible': true,

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

              'status': item.status,
              'full': item.full,
              'refill': item.refill,
              'upper_value': item.upper_value,
              'lower_value': item.lower_value,

              'color_sm_status': item.color_sm_status,
              'color_ppm_avg': item.color_ppm_avg,

              'measurement_type': item.measurement_type,
              'last_reading': item.pulse_1 ? item.pulse_1 : '00000000',

              'charging': item.charging,
              'battLevel': item.bp,
              'pump_status': false,

              'NO3_avg': item.NO3_avg,
              'NH4_avg': item.NH4_avg,

              'nutrient_lower': 'nutrient_lower' in item ? item.nutrient_lower.toString() : null,
              'nutrient_upper': 'nutrient_upper' in item ? item.nutrient_upper.toString() : null,
              'nutrient_gauge': 'nutrient_gauge' in item ? item.nutrient_gauge.toString() : null,
              'nutrient_avg': 'nutrient_avg' in item ? item.nutrient_avg.toString() : null,
              'nutrient_label': 'nutrient_label' in item ? item.nutrient_label : null,

              'sm_gauge': 'sm_gauge' in item ? item.sm_gauge : 0,
              'temp_gauge': 'temp_gauge' in item ? item.temp_gauge : 0,
              'temp_avg': 'temp_avg' in item ? item.temp_avg : 0,
              'temp_uom': 'temp_uom' in item ? item.temp_uom : 0,
              'sm_avg': 'sm_avg' in item ? item.sm_avg : 0,
              // 'ambient_temp': 'ambient_temp' in item ? item.ambient_temp : 0,

              'perimeter': item.perimeter !== null ? { type: 'geojson', data: JSON.parse(item.perimeter) } : null,
              'layer': (typeof item.layer === 'object' && item.layer !== null) ? (JSON.parse(JSON.stringify(item.layer))) : null,

              'zones': item.zones !== null ? item.zones : null,

              'marker_outline_color': item.marker_outline_color

            });
          }
        });

      }
    },

    toggleBurger() {
      document.querySelector('.custom-nav-toggler').className = this.toggleVisible ? "custom-nav-toggler visible" : "custom-nav-toggler hidden";
      this.toggleVisible = !this.toggleVisible;
    },

    focusMarker(marker) {
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

    showFieldModal(node_address) {
      let item = this.markers.find((m) => {
        return m.node_address == node_address;
      });
      if (item) {
        this.field_model = item;
        this.fieldModalVisible = true;
      }
    },

    setFieldModalZoneInfo(node_address, zone) {
      this.active_zone = zone;
      this.showFieldModal(node_address);
    },

    showLayerCursor() {
      this.map.getCanvas().style.cursor = 'pointer';
    },

    hideLayerCursor() {
      this.map.getCanvas().style.cursor = '';
    },

    closeFieldModal() {
      this.active_zone = null;
      this.field_model = this.empty_field_model;
      this.fieldModalVisible = false;
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
      if (type == 'green_zone') {
        return '<div style="display:inline-block; border:1px solid black; width:100%; height:0; padding-bottom:100%; background:#00FF00;"></div>';
      }
      if (type == 'lightgreen_zone') {
        return '<div style="display:inline-block; border:1px solid black; width:100%; height:0; padding-bottom:100%; background:#DAFF00;"></div>';
      }
      if (type == 'yellow_zone') {
        return '<div style="display:inline-block; border:1px solid black; width:100%; height:0; padding-bottom:100%; background:#FFDA00;"></div>';
      }
      if (type == 'red_zone') {
        return '<div style="display:inline-block; border:1px solid black; width:100%; height:0; padding-bottom:100%; background:#FE0000;"></div>';
      }
    },

    goToManageNutrients(marker) {
      this.$router.push({
        name: "nutrients_edit",
        params: {
          node_id: marker.node_id,
          node_address: marker.node_address,
          field_id: marker.field_id,
          field_name: marker.field_name,
          graph_type: marker.graph_type,
          graph_start_date: marker.graph_start_date
        }
      });
    },

    goToManageSM(marker) {
      this.$router.push({
        name: "soil_moisture_edit",
        params: {
          node_id: marker.node_id,
          node_address: marker.node_address,
          field_id: marker.field_id,
          field_name: marker.field_name,
          graph_type: marker.graph_type,
          graph_start_date: marker.graph_start_date
        }
      });
    },

    goToGraph(marker) {
      if (this.userCan('Graph', 'Soil Moisture', marker.node_id, 'O')) {
        this.$router.push({
          name: "soil_moisture_graph",
          params: {
            node_id: marker.node_id,
            node_address: marker.node_address,
            field_id: marker.field_id,
            field_name: marker.field_name,
            graph_type: marker.graph_type,
            graph_start_date: marker.graph_start_date
          }
        });
      }
    }
  },

  beforeRouteLeave(to, from, next) {
    // if(this.refreshTimer){
    //   clearInterval(this.refreshTimer);
    // }
    next();
  }
};

</script>
<style>
ul {
  list-style-type: none;
  padding: 0;
}

.mab_modal .modal-header {
  padding: 10px;
  padding-bottom: 0px;
}

.modal-body {
  padding: 10px;
}

.modal-footer .btn {
  position: absolute;
  bottom: 15%;
}

.modal-footer {
  padding: 0;
  padding-right: 10px;
}

.avg-styles {
  position: relative;
}

.avg-styles ul {
  position: absolute;
  bottom: 10%;
}

.avg-styles li {
  font-weight: bold;
}

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

.marker_visible {
  display: block;
}

.marker_hidden {
  display: none;
}

/* Focus menu */

/* Focus Menu - Search Field */

.fields_search {
  padding: 0.5rem;
  height: 2rem;
  /*margin: 0.5rem;*/
  /*width: calc(100% - 1rem);*/
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
  margin-bottom: 1px;
  margin-right: 0.5rem;
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

.ps__rail-y {
  z-index: 99;
}

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

.marker.no_perimeter:hover {
  cursor: pointer;
}

.marker.has_perimeter:hover {
  cursor: auto !important;
}

.mapboxgl-marker {
  cursor: auto !important;
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

.status-text {
  display: block;
  margin: auto;
  width: 100%;
  text-align: center;
  margin-top: -60px !important;
  z-index: 99999999;
  position: relative;
  font-size: 13px;
}
</style>

