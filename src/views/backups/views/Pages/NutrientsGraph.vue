<template>
  <div class="content">
    <base-header class="pb-6" type="nutrients">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Nutrients - {{ field_name }} ( {{ current_node }} ) - Graph
          </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <div class='row'>
        <div class='col-md-12'>
          <card body-classes="px-0 py-0">
            <template slot="header">
              <div class='row'>
                <div class='col-md-12'>
                  <base-button :disabled="!node_id || !userCan('Export', 'Nutrients', node_id, 'O')"
                    @click.native="exportGraphCSV()" class="btn" size="sm" type="primary" v-b-tooltip.hover.top
                    title="Export device data to a CSV file." icon>
                    Export CSV
                  </base-button>

                  <base-button :disabled="!node_id || !userCan('Edit', 'Nutrients', node_id, 'O')"
                    @click.native="goToManage()" class="btn" size="sm" type="primary" v-b-tooltip.hover.top
                    title="Navigate to the nutrient node's management screen" icon>
                    Manage Nutrients
                  </base-button>
                </div>
              </div>
            </template>
            <div class="card-body">
              <b-row>
                <!-- <b-col md="6"> -->
                <b-col md="4" v-if="related_nodes.length > 1">
                  <base-input label="Current Node">
                    <el-select v-model="current_node" :disabled="loading">
                      <template v-for="(node, cnidx) in related_nodes">
                        <el-option :key="cnidx" :label="node" :value="node"
                          :selected='$route.params.node_address'></el-option>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col md="4">
                  <base-input label="Graph Type">
                    <el-select v-model="graph_type" :disabled="loading">
                      <el-option label="Nutrients (RAW)" value='nutrient'></el-option>
                      <!-- <el-option label="Nutrient (PPM)" value='nutrient_ppm'></el-option> -->
                      <el-option label="Nutrient (depth in inches/mm)" value='nutrient_ppm'></el-option>
                      <!-- <el-option label="Nutrients (PPM Average)" value='nutrient_ppm_avg'></el-option> -->
                      <el-option label="Nutrients Type Average" value='nutrient_type_avg'></el-option>
                      <el-option label="Soil Moisture Average" value='nutrient_sm_avg'></el-option>
                      <el-option label="Soil Temp Average" value='nutrient_temp_avg'></el-option>
                      <!-- <el-option label="Nutrients EC Average" value='nutrient_ec_avg'></el-option> -->
                    </el-select>
                  </base-input>
                </b-col>

                <div class="w-100"></div>

                <b-col md="4">
                  <base-input label="Range">
                    <el-select v-model="sub_days" :disabled="loading" v-b-tooltip.hover.top
                      title="Choose the time range of data to view.">
                      <el-option label="Last 24 Hours" value='1'></el-option>
                      <el-option label="Last Week" value='7'></el-option>
                      <el-option label="Last Two Weeks" value='14'></el-option>
                      <el-option label="Last Month" value='31'></el-option>
                      <el-option label="Last Year" value='365'></el-option>
                      <el-option label="Custom" value='custom'></el-option>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col md="4">
                  <base-input v-show="sub_days == 'custom'" label="Start Date">
                    <flat-picker slot-scope="{focus, blur}" @on-open="focus" @on-close="blur" v-model="graph_start_date"
                      :config="{ maxDate: date_now }" placeholder="Custom Date" class="form-control datepicker"
                      v-b-tooltip.hover.top title="A custom start date allows a custom range of data to be viewed.">
                    </flat-picker>
                  </base-input>
                </b-col>
                <!-- </b-col> -->

                <b-col md="2" class="graph_margin">
                  <!-- the gauge. -->
                  <highcharts class="hc" :options="gaugeOptions" :update-args="chartUpdateArgs" ref="hchart"></highcharts>
                </b-col>
                <b-col md="2" class="graph_margin">
                  <!-- the gauge. -->
                  <highcharts class="hc" :options="gauge2Options" :update-args="chartUpdateArgs" ref="hchart">
                  </highcharts>
                </b-col>
              </b-row>
              <div class="the_graph">
                <highcharts class="hc" :options="chartOptions" :update-args="chartUpdateArgs" ref="hchart"></highcharts>
              </div>
              <b-row>
                <b-col class='text-center'>
                  <b-form-checkbox v-if="graph_type == 'sm'" @change="toggleSeriesDragDrop" v-model="zoomOrDrag"
                    name="check-button" size="lg" switch>Mode: {{ zoomOrDrag ? "Drag" : "Zoom" }}</b-form-checkbox>
                </b-col>
              </b-row>
            </div>
          </card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-new */
import { Select, Option } from 'element-ui'
import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import Highcharts from 'highcharts';
import mab_utils from '../../util/mab-utils';
import NiftyGauge from '../../custom_components/NiftyGauge';
import swal from 'sweetalert2';

// Tweak to prevent Tooltip from disappearing (Testing working)
Highcharts.Pointer.prototype.reset = function () { return undefined; };

export default {

  mixins: [mab_utils],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    flatPicker,
    NiftyGauge
  },

  data() {
    return {
      loading: false,
      in_progress: true,

      tooltip: {
        enabled: true,
      },

      NO3_avg: 0,
      NH4_AVG: 0,

      current_node: this.$route.params.node_address,

      node_id: null,
      field_name: null,

      graph_start_date: '',
      graph_type: '',
      sub_days: '',

      bInitialQuery: true,
      bNotified: false,

      lower_limit: 0,
      low_lim: 0,

      upper_limit: 0,
      upper_lim: 0,

      related_nodes: [],

      data: [],

      date_now: '',
      series: [],
      chartUpdateArgs: [true, true, { duration: 1000 }],
      seriesAdjusted: false,
      zoomOrDrag: false, /* false == zoom, true == drag */
      lastDate: null,
      exportFileName: '',

      initial_start_date: null,
      initial_end_date: null,

      selection_start: null,
      selection_end: null,

      nutrient_lower: 0,
      nutrient_upper: 0,
      nutrient_gauge: 0,
      nutrient_avg: 0,
      nutrient_label: '',
      nutrient_NO3: 0,
      nutrient_NH4: 0,
      M3_1: 0,

      yMaxBackup: 0,
      yMinBackup: 0,

      yMin: null,
      yMax: null,

      xMin: 1000,
      xMax: 0,

      lastTimestamp: null,

      full: 0,
      refill: 0,

      n_template: 0,

      plotOptions: {
        series: {
          states: {
            inactive: {
              opacity: 1
            }
          }
        }
      },
      chartTitle: {
        text: "Loading..",
        widthAdjust: -300
      },
      plotLines: [],
      resetButton: {
        text: "Reset",
        enabled: true,
        onclick: (e) => {
          this.resetSeriesData();
        }
      }
    }
  },

  computed: {
    chartOptions() {

      return {
        credits: { enabled: false },
        chart: {
          marginTop: 180,
          type: 'line',
          zoomType: this.zoomOrDrag == false ? 'x' : false,
          panning: true,
          panKey: 'shift',
          height: 600,
          resetZoomButton: { position: { x: 0, y: -50 } },
          scrollablePlotArea: {
            minWidth: 700,
            scrollPositionX: 1
          },
        },
        exporting: {
          buttons: {
            contextButton: { enabled: false },
            exportButton: { text: 'Export', menuItems: ['downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG'] },
            printButton: { text: 'Print', onclick: function () { this.print(); } },
            resetButton: this.resetButton
          },
          filename: this.exportFileName
        },
        colors: ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
        series: this.series,
        xAxis: {
          min: this.xMin,
          max: this.xMax,
          type: 'datetime',
          dateTimeLabelFormats: {
            millisecond: '%H:%M:%S.%L',
            second: '%H:%M:%S',
            minute: '%H:%M',
            hour: '%H:%M',
            day: '<span style="fill: #00A04C; font-weight:600">%e %b</span>',
            week: '%e %b',
            month: '%b %Y',
            year: '%Y'
          },
          tickPosition: 'inside',
          labels: {
            rotation: -45,
          },
          crosshair: true,

          events: {
            setExtremes: function (e) {
              var c = this;
              setTimeout(function () {
                if (typeof e.min == 'undefined' && typeof e.max == 'undefined') {
                  // 'zoom-reset'
                  c.chart.yAxis[0].setExtremes(c.chart.yAxis[0].min, c.chart.yAxis[0].max);
                } else {
                  // 'zoom-in'
                  c.chart.yAxis[0].setExtremes();
                }
              }, 1);
            },
            afterSetExtremes: this.afterSetExtremes
          }

        },
        yAxis: {
          tickInterval: 1,
          max: this.yMax,
          min: this.yMin,
          minPadding: 0,
          maxPadding: 0,
          title: { text: '' },
          plotLines: this.plotLines,
        },
        legend: {
          layout: 'horizontal',
          align: 'center',
          verticalAlign: 'bottom',
          labelFormatter: function () {
            return this.name;
          },
        },
        tooltip: {
          shape: "square",
          headerShape: "callout",
          borderWidth: 0,
          shadow: false,
          useHTML: true,
          positioner: function () {
            return { x: 50, y: 0 };
          },
          split: true,
          distance: 30,
          padding: 5,
          formatter() {
            let s = "";
            s = '<span class="date_of_reading" style="color:#444;">' + Highcharts.dateFormat('%A, %b %e %Y, %H:%M:%S', new Date(this.x)) + '</span><br>';
            this.points.forEach(function (point) {
              s += '<span style="color:' + point.series.color + '; font-size:13px;">●</span> <span style="color:#444;font-size:13px;font-weight:bold">' + point.series.name + ': </span><span style="color:firebrick;">' + point.y + '</span> <img src="https://img.icons8.com/ios-filled/15/000000/vertical-line.png"/> ';
            });
            return s;
          },
          shared: true,
        },
        plotOptions: this.plotOptions,
        title: this.chartTitle,
        //   responsive: {
        //     rules: [{
        //       condition: {
        //         maxWidth: 300
        //       },
        //       chart: {
        //         marginTop: -300,
        //         type: 'line',
        //         scrollablePlotArea: {
        //           minWidth: 300,
        //           scrollPositionX: 1,
        //           maxWidth:400
        //         },
        //       },
        //       chartOptions: {
        //         legend: {
        //           align: 'center',
        //           verticalAlign: 'bottom',
        //           layout: 'horizontal'
        //         },
        //         yAxis: {
        //           tickInterval: 1,
        //           max: this.yMax,
        //           min: this.yMin,
        //           minPadding: 0,
        //           maxPadding: 0,
        //           title: { text: '' },
        //           plotLines: this.plotLines,
        //         },
        //       }
        //     }]
        //   }
      }
    },

    gaugeOptions() {

      var low = parseFloat(this.low_lim);
      var upper = parseFloat(this.upper_lim);

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

        title: null,

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        // the value axis
        yAxis: {
          stops: [
            [0.8, 'red'],
            [1.0, 'green']
          ],
          lineWidth: 0,
          tickInterval: 10,
          minorTickInterval: 2.5,
          title: null,
          labels: {
            y: -5,
            style: {
              'color': 'black',
              'fontWeight': 'bold'
            }
          },

          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 20,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [
            {
              from: 30,
              to: 50,
              color: '#f44536', // red
              thickness: 30,
              borderWidth: 0,
            },
            {
              from: 30,
              to: 32,
              color: 'black', // red
              thickness: 30,
              borderWidth: 0,
              // label: {
              //   text: 'Upper'
              // }
            },
            {
              from: 10,
              to: 30,
              color: '#fbe13b', // yellow
              thickness: 30
            },
            {
              from: -10,
              to: 10,
              color: '#83c14b', // green
              thickness: 30
            },
            {
              from: -10,
              to: -30,
              color: '#fbe13b', // yellow
              thickness: 30,
              // label: {
              //   text: 'L',
              //   textAlign: 'center',
              //   x: 122,
              //   y: 16,
              // }
            },
            {
              from: -12,
              to: -14,
              color: 'black',
              thickness: 30,
              // label: {
              //   text: 'Lower',
              // }
            },
            {
              from: -50,
              to: -30,
              color: '#f44536', // red
              thickness: 30
            },
          ]
        },

        series: [{
          name: 'M6.1',
          data: [this.nutrient_NO3],
          dataLabels: {
            format: 'N03 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
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

    gauge2Options() {

      var low = parseFloat(this.low_lim);
      var upper = parseFloat(this.upper_lim);

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

        title: null,

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        yAxis: {
          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 15,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [
            {
              from: 30,
              to: 50,
              color: '#f44536', // red
              thickness: 30,
              borderWidth: 0,
            },
            {
              from: 10,
              to: 30,
              color: '#fbe13b', // yellow
              thickness: 30
            },
            {
              from: 25,
              to: 27,
              color: 'black', // black
              thickness: 30,
              borderWidth: 0,
              // label: {
              //   text: 'Upper'
              // }
            },
            {
              from: -10,
              to: 10,
              color: '#83c14b', // green
              thickness: 30
            },
            {
              from: -10,
              to: -30,
              color: '#fbe13b', // yellow
              thickness: 30,
              // label: {
              //   text: 'L',
              //   textAlign: 'center',
              //   x: 122,
              //   y: 16,
              // }
            },

            {
              from: -50,
              to: -30,
              color: '#f44536', // red
              thickness: 30
            },
            {
              from: -35,
              to: -37,
              color: 'black',
              thickness: 30,
            },
          ]
        },

        series: [{
          name: 'M6.1',
          // this returns 0 because it takes 0 first. 
          data: [this.nutrient_NH4],
          // tooltip: {
          //     valueSuffix: ' (PPM)'
          // },
          dataLabels: {
            format: 'NH4 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
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

  watch: {
    title: function (val) {
      this.chartOptions.title = val;
    },
    plotOptions: {
      handler: function (val) {
        if (val.length) {
          this.chartOptions.plotOptions = val;
        }
      },
      deep: true
    },
    series: {
      handler: function (val) {
        if (val.length) {
          this.chartOptions.series = val;
        }
      },
      deep: true
    },
    graph_start_date: function (val) {
      this.changeDateRange();
    },
    graph_type: function (val) {
      this.maybeReloadGraphData();
    },
    sub_days: function (val) {
      if (val == 'custom' && !this.graph_start_date) { return; }
      if (val != 'custom') { this.graph_start_date = ''; }
      // Experimental
      this.initial_start_date = null;
      this.initial_end_date = null;
      this.maybeReloadGraphData();
    },
    current_node: function (val) {
      this.bNotified = false;
      this.maybeReloadGraphData();
    }
  },

  methods: {

    loadGraphData() {

      this.loading = true;
      this.in_progress = true;

      this.$refs.hchart.chart.showLoading(this.bInitialQuery ? "Loading..." : "Refreshing...");

      return this.$axios.post("/api/Graph", {
        node_address: this.current_node,
        graph_type: this.graph_type,
        graph_start_date: this.graph_start_date,
        sub_days: this.sub_days,
        is_initial: this.bInitialQuery,
        selection_start: this.selection_start,
        selection_end: this.selection_end
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
            return;
          }

          if (!this.bNotified && resp.data.not_current && resp.data.last_date) {
            swal.fire({
              title: 'No recent data found',
              text: "Last readings up to " + resp.data.last_date + " could be displayed.",
              showCancelButton: false,
              confirmButtonText: 'OK',
              buttonsStyling: false,
              customClass: {
                cancelButton: 'btn btn-outline-primary',
                confirmButton: 'btn btn-primary'
              }
            });
            this.bNotified = true;
          }

          this.selection_start = null;
          this.selection_end = null;

          // COMMON DATA
          this.node_id = resp.data.node_id;
          this.field_name = resp.data.field_name;

          // Experimental
          if (this.bInitialQuery) {
            this.graph_start_date = resp.data.graph_start_date;
            this.graph_type = resp.data.graph_type;
            this.sub_days = resp.data.sub_days;
          }

          this.nutrient_lower = 'nutrient_lower' in resp.data ? resp.data.nutrient_lower : 0;
          this.nutrient_upper = 'nutrient_upper' in resp.data ? resp.data.nutrient_upper : 100;
          this.nutrient_gauge = 'nutrient_gauge' in resp.data ? resp.data.nutrient_gauge : 0;
          this.nutrient_avg = 'nutrient_avg' in resp.data ? resp.data.nutrient_avg : 0;
          this.nutrient_label = 'nutrient_label' in resp.data ? resp.data.nutrient_label : '';
          this.NO3_avg = 'nutrient_NO3' in resp.data ? resp.data.NO3_avg : 0;
          this.NO3_avg = 'nutrient_NH4' in resp.data ? resp.data.NH4_avg : 0;

          this.xMax = resp.data.x_max;
          this.xMin = resp.data.x_min;

          this.data = resp.data;

          this.lower_limit = resp.data.lower_limit;
          this.low_lim = parseFloat(this.lower_limit);
          // this.lower_limit = parseFloat(this.lower_limit);

          this.upper_limit = resp.data.upper_limit;
          this.upper_lim = parseFloat(this.upper_limit);


          //console.log(parseFloat(this.nutrient_NH4).toFixed(1));

          // EXPERIMENTAL
          // if(this.graph_type == 'nutrient_ppm_avg'){
          //   this.yMax = resp.data.y_max;
          //   this.yMin = resp.data.y_min;
          // } else {
          //   this.yMax = null;
          //   this.yMin = null;
          // }

          // if (this.graph_type == 'nutrient_ppm') {
          //   this.yMax = resp.data.y_max;
          //   this.yMin = resp.data.y_min;
          //   // console.log(this.data.nutrient_NH4);
          // } else {
          //   this.yMax = null;
          //   this.yMin = null;
          // }

          if (this.graph_type == 'nutrient_type_avg') {
            this.yMax = resp.data.y_max;
            this.yMin = resp.data.y_min;
            // console.log(this.data.nutrient_NH4);
          } else {
            this.yMax = null;
            this.yMin = null;
          }

          if (this.graph_type == 'nutrient_sm_avg') {
            this.yMax = resp.data.y_max;
            this.yMin = resp.data.y_min;
            // console.log(this.data.nutrient_NH4);
          } else {
            this.yMax = null;
            this.yMin = null;
          }

          if (this.graph_type == 'nutrient_temp_avg') {
            this.yMax = resp.data.y_max;
            this.yMin = resp.data.y_min;
            // console.log(this.data.nutrient_NH4);
          } else {
            this.yMax = null;
            this.yMin = null;
          }

          if (this.graph_type == 'nutrient_ec_avg') {
            this.yMax = resp.data.y_max;
            this.yMin = resp.data.y_min;
            // console.log(this.data.nutrient_NH4);
          } else {
            this.yMax = null;
            this.yMin = null;
          }
          // EXPERIMENTAL

          this.nutrient_NO3 = this.data.nutrient_NO3;
          this.nutrient_NH4 = this.data.nutrient_NH4;

          this.yMaxBackup = resp.data.y_max; // used with toggle switch
          this.yMinBackup = resp.data.y_min; // used with toggle switch

          this.full = resp.data.full;
          this.refill = resp.data.refill;


          this.lastDate = resp.data.last_date.replace(":", '-');
          this.exportFileName = resp.data.graph.field_name + '-' + this.graph_type + '-' + this.$route.params.node_address + '-' + this.lastDate;

          this.plotOptions = resp.data.graph.plotOptions;

          this.chartTitle = resp.data.graph.title;

          this.series = resp.data.graph.series.filter((series) => { if (series.data) { return series.data.length > 0; } else return false; });

          this.lastTimestamp = resp.data.x_max;

          if (this.graph_type == 'nutrient' || this.graph_type == 'nutrient_ppm') {
            if (this.zoomOrDrag) {
              this.enableSeriesDragDrop();
            } else {
              this.disableSeriesDragDrop();
            }
          } else {
            this.disableSeriesDragDrop();
          }

          if (this.graph_type == 'nutrient_ppm_avg') {
            this.showPlotLines();
            this.setupCustomTooltipstraight();
          }
          else {
            this.hidePlotLines();
            this.removeCustomTooltip();
          }

          if (this.graph_type == 'nutrient_type_avg') {
            this.showPlotLines();
            this.setupCustomTooltipstraight();
          }
          else {
            this.hidePlotLines();
            this.removeCustomTooltip();
          }

          if (this.graph_type == 'nutrient_sm_avg') {
            this.showPlotLines();
            this.setupCustomTooltip();
          }
          else {
            this.hidePlotLines();
            this.removeCustomTooltip();
          }

          this.related_nodes = resp.data.related_nodes;
          this.$refs.hchart.chart.reflow();
        }
        setTimeout(() => { this.in_progress = false; this.bInitialQuery = false; }, 1);
        return resp;
      });
    },

    maybeReloadGraphData() {
      if (this.in_progress == true) { return; }
      this.loadGraphData();
    },

    changeDateRange() {
      if (this.in_progress == true) { return; }
      if (this.graph_start_date == '') { return; }
      this.loadGraphData();
    },

    enableSeriesDragDrop() {
      if (this.series && this.series.length) {

        // Enable shared tooltip
        this.chartOptions.tooltip = { shared: true };
        // Enable custom Reset button
        this.resetButton.enabled = true;

        this.series.forEach((series, idx) => {
          // Set custom Per Series Data
          this.series[idx].custom = {
            moved: false
          };
          // Enable DnD for this range of series only
          this.series[idx].dragDrop = {
            draggableX: false,
            draggableY: true,
            dragMinY: 0,
            dragMaxY: 100,
            groupBy: 'grp'
          };
          // Track changes
          this.series[idx].point = {
            events: {
              dragStart: function () {
                this.series.options.custom.moved = true;
              },
            }
          };
          // Format tooltip: Hide value once series was moved
          this.series[idx].tooltip = {
            pointFormatter: function () {
              return '<span style="color:' + this.series.color + ';">●</span> ' + this.series.name + ': <b>' +
                (this.series.options.custom.moved ? '' : Highcharts.numberFormat(this.y, 1)) + '</b><br/>';
            }
          };
        });

        // Lock in Y Max/Min
        if (this.graph_type != 'ave') {
          this.yMax = this.yMaxBackup;
          this.yMin = this.yMinBackup;
        }

        this.chartTriggerChange();
      }
    },

    disableSeriesDragDrop() {
      if (this.series && this.series.length) {

        // Disable drag
        this.series.forEach((series, idx) => {
          this.series[idx].dragDrop = {
            draggableX: false,
            draggableY: false,
          };
        });
        // Disable shared tooltip
        this.chartOptions.tooltip = { shared: false };
        // Disable custom Reset button
        this.resetButton.enabled = false;

        // Disable Y Max/Min
        if (this.graph_type != 'ave') {
          this.yMax = null;
          this.yMin = null;
        }

        this.chartTriggerChange();
      }
    },

    toggleSeriesDragDrop() {
      // Zoom
      if (this.zoomOrDrag) {
        this.enableSeriesDragDrop();
        // Drag
      } else {
        this.disableSeriesDragDrop();
      }
    },

    showPlotLines() {
      this.plotLines = [
        { /* Full Line */
          value: this.full,
          color: '#00F',
          width: 2,
          label: {
            x: 0,
            align: 'right',
            formatter: () => { return 'Full: ' + this.full; }
          }
        },
        { /* Refill Line */
          value: this.refill,
          color: '#D26761',
          width: 2,
          label: {
            x: 0,
            align: 'right',
            formatter: () => { return 'Refill: ' + this.refill; }
          }
        }
      ];
    },

    hidePlotLines() {
      this.plotLines = [];
    },

    // Hack to trigger chart update via faux change
    chartTriggerChange() {
      this.series.push({ type: 'line', name: 'Faux', data: [] });
      this.series.pop();
    },

    exportGraphCSV() {

      this.zoomOrDrag = false;

      // accessing internal series data from the outside
      let series = this.$refs.hchart.chart.series;
      try {
        for (var i = 0; i < series.length; i++) {
          if (typeof series[i].options.custom.moved !== "undefined") {
            if (series[i].options.custom.moved == true) {
              this.seriesAdjusted = true;
              break;
            }
          }
        }
      } catch (err) { }

      if (this.seriesAdjusted) {
        this.resetSeriesData().then((resp) => {
          this.$refs.hchart.chart.downloadCSV();
        });
      } else {
        this.$refs.hchart.chart.downloadCSV();
      }
    },

    resetSeriesData() {
      this.seriesAdjusted = false;
      return this.loadGraphData();
    },

    setupCustomTooltip() {
      for (var i = 0; i < this.series.length; i++) {
        if (this.series[i].name == 'Average') {
          this.series[i].tooltip = {
            pointFormatter: function () {
              let str = '<span style="color:' + this.series.color + ';">●</span> ' + this.series.name + ': <b>' + this.y + '</b><br>';
              return str;
            }
          };
        }
        else if (this.series[i].name == 'Avg SM') {
          this.series[i].tooltip = {
            pointFormatter: function () {
              let str = '<span style="color:' + this.series.color + ';">●</span> ' + this.series.name + ': <b>' + this.y + '%</b><br>';
              return str;
            }
          };
        }
      }
    },

    setupCustomTooltipstraight() {
      for (var i = 0; i < this.series.length; i++) {
        this.series[i].tooltip = {
          pointFormatter: function () {
            let str = '<span style="font-size:25px; color:black;">●</span> ' + this.series.name + ': <b>' + this.y + '</b><br>';
            return str;
          }
        };
      }
    },

    avgCustomTooltipstraight() {
      for (var i = 0; i < this.series.length; i++) {
        this.series[i].tooltip = {
          pointFormatter: function () {
            let str = '<span style="font-size:25px; color:black;">●</span> ' + this.series.name + ': <b>' + this.y + '%</b><br>';
            return str;
          }
        };
      }
    },

    removeCustomTooltip() {
      for (var i = 0; i < this.series.length; i++) {
        if (this.series[i].tooltip && this.series[i].tooltip.pointFormatter) {
          delete this.series[i].tooltip.pointFormatter;
        }
      }
    },

    goToManage() {
      this.$router.push({
        name: 'nutrients_edit',
        params: { node_address: this.current_node }
      });
    },

    afterSetExtremes(e) {
      // initial load
      if (e.trigger == null) {
        if (this.bInitialQuery) {
          this.initial_start_date = this.xMin;
          this.initial_end_date = this.xMax;
          this.bInitialQuery = false;
        }
      } else {
        if (e.target.chart.resetZoomButton) {
          this.selection_start = e.min;
          this.selection_end = e.max;
        } else {
          this.selection_start = this.initial_start_date;
          this.selection_end = this.initial_end_date;
        }
        this.maybeReloadGraphData();
      }
    },
  },

  created() {
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    var yyyy = today.getFullYear();
    this.date_now = today = yyyy + '-' + mm + '-' + dd;
  },

  mounted() {
    this.loadGraphData();
  }
};
</script>
<style>
.highcharts-tooltip {
  pointer-events: auto !important;
}

/* .highcharts-tooltip-container .highcharts-tooltip>span {
  min-width: 400px;
  max-width: 400px;
  white-space: normal;
  overflow-y:scroll !important;
  height:auto !important;
  white-space:normal !important; 
}

.highcharts-tooltip span {
    height:auto !important;
    width:400px !important;
    max-width:400px !important;
    overflow-y:scroll !important;
    white-space:normal !important; 
} */


.highcharts-tooltip-box span:first-child {
  font-size: 15px;
  color: #fff;
  white-space: nowrap;
  /* overflow-x: auto;
    overflow-y: hidden; */
  /* white-space: nowrap; */
  white-space: normal !important;
  margin: 4px, 4px;
  padding: 18px;
  background-color: #fff;
  width: 95% !important;
  right: 8px !important;
  margin: 0 auto !important;
  left: unset !important;
  top: 0 !important;
  text-align: center;
  border-radius: 2px;
  border: 5px solid #01a04c;
}

.highcharts-tooltip-box,
.highcharts-tooltip {
  right: 0 !important;
  margin: 0 auto !important;
  left: 0 !important;
}

.highcharts-tooltip-box {
  top: 125px !important;
}

.highcharts-tooltip {
  top: -88px !important;
}

.highcharts-label-box {
  fill: transparent;
}

.date_of_reading {
  text-align: center;
  display: block;
  position: sticky;
  padding: 5px !important;
  border: 0 !important;
  font-weight: bold;
  padding-top: 8px !important;
}

.graph_margin {
  margin-top: -100px;
}

@media only screen and (max-width: 750px) {

  .date_of_reading {
    color: #444;
    /* position: absolute !important; */
    text-align: center !important;
    display: block;
    width: 100% !important;
    padding-bottom: 2px !important;
    border: 5px solid #01a04c !important;
    border-bottom: 0 !important;
    background: transparent;
    margin-bottom: 10px !important;
  }

  .graph_margin {
    margin-top: 0px;
  }

  .highcharts-tooltip-box span:first-child {
    text-align: center;
    padding: 15px;
    width: auto;
    position: fixed !important;
  }

  .highcharts-tooltip-box {
    top: 70px !important;
  }
}
</style>
