<template>
  <div class="content">
    <base-header class="pb-6" type="soilmoisture">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Soil Moisture - {{ field_name }} ( {{ current_node }} ) -
            Graph
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
                  <base-button :disabled="!node_id || !userCan('Export', 'Soil Moisture', node_id, 'O')"
                    @click.native="exportGraphCSV()" class="btn" size="sm" type="primary" v-b-tooltip.hover.top
                    title="Export device data to a CSV file." icon>
                    Export CSV
                  </base-button>

                  <base-button :disabled="!node_id || !userCan('Edit', 'Soil Moisture', node_id, 'O')"
                    @click.native="goToManage()" class="btn" size="sm" type="primary" v-b-tooltip.hover.top
                    title="Navigate to the soil moisture node's management screen" icon>
                    Manage SM
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
                      <template v-for="(node, index) in related_nodes">
                        <el-option :key="index" :label="node" :value="node"
                          :selected='$route.params.node_address'></el-option>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col md="4">
                  <base-input label="Graph Type">
                    <el-select v-model="graph_type" :disabled="loading">
                      <el-option label="Separate Levels" value='sm' selected></el-option>
                      <el-option label="Stacked Graph" value='stack'></el-option>
                      <el-option label="Sum" value='sum'></el-option>
                      <el-option label="Average" value='ave'></el-option>
                      <el-option label="Temperature" value='temp'></el-option>
                      <el-option label="Technical" value='tech'></el-option>
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



      current_node: this.$route.params.node_address,

      node_id: null,
      field_name: null,
      data_grouping: false,


      graph_start_date: '',
      graph_type: '',
      sub_days: '',

      bInitialQuery: true,
      bNotified: false,

      related_nodes: [],

      labelsEnabled: true,

      reversed: false,

      //data: [],

      date_now: '',
      series: [],
      chartUpdateArgs: [true, true, { duration: 1000 }],
      seriesAdjusted: false,
      zoomOrDrag: false, /* false == zoom, true == drag */
      stacking: 'normal',
      lastDate: null,
      exportFileName: '',

      initial_start_date: null,
      initial_end_date: null,

      selection_start: null,
      selection_end: null,


      yMaxBackup: 0,
      yMinBackup: 0,

      yMin: null,
      yMax: null,

      xMin: 1000,
      xMax: 0,

      lastTimestamp: null,

      full: 0,
      refill: 0,

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
          marginTop: 160,
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
        responsive: {
          rules: [{
            condition: {
              maxWidth: 800,
            },
            chartOptions: {
              legend: {
                enabled: true,
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom',
                labelFormatter: function () {
                  return this.name;
                },
                x: 0,
                y: 0
              },
            }
          }],
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
            //setTimeout(() => { this.in_progress = false; }, 1);
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
          this.xMax = resp.data.x_max;
          this.xMin = resp.data.x_min;
          //this.yMin = resp.data.y_min;

          if (this.graph_type == 'ave') {
            this.yMax = resp.data.y_max;
            this.yMin = resp.data.y_min;
          } else {
            this.yMax = null;
            this.yMin = null;
          }

          if (this.graph_type == 'stack') {
            this.reversed = false;
          }
          // else {
          //   this.reversed = false;
          // }

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

          if (this.graph_type == 'sm' || this.graph_type == 'stack') {
            if (this.zoomOrDrag) {
              this.enableSeriesDragDrop();
            } else {
              this.disableSeriesDragDrop();
            }
          } else {
            this.disableSeriesDragDrop();
          }

          // if (this.graph_type == 'stack') {
          //   if (this.zoomOrDrag) {
          //     this.enableSeriesDragDrop();
          //   } else {
          //     this.disableSeriesDragDrop();
          //   }
          // } else {
          //   this.disableSeriesDragDrop();
          // }

          if (this.graph_type == 'ave') {
            this.showPlotLines();
            this.setupCustomTooltip();
          } else {
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
        console.log("Enabling Series Drag and Drop");
        console.log(this.series);

        if (this.graph_type == 'stack') {
          // Remove labels on yAxis when DnD is true
          this.labelsEnabled = false;
        } else {
          this.labelsEnabled = true;
        }

        // Enable shared tooltip
        this.chartOptions.tooltip = { shared: true };
        // Enable custom Reset button
        this.resetButton.enabled = true;

        this.series.forEach((series, idx) => {

          // Set custom Per Series Data
          this.series[idx].custom = {
            moved: false
          };

          this.series[idx].pointStart = Date.UTC(2004, 3, 1);
          this.series[idx].pointInterval = 3600 * 1000;

          // Enable DnD for this range of series only
          this.series[idx].dragDrop = {
            draggableX: false,
            draggableY: true,
            dragMinY: 0,
            dragMaxY: 100,
            groupBy: 'grp',
          };

          this.series[idx].dragDrop.draggableX = false;
          this.series[idx].dragDrop.draggableY = true;
          if (this.series[idx].name != 'Average') {
            this.series[idx].dragDrop.groupBy = 'grp' + (idx + 1);
          } else {
            this.series[idx].dragDrop.groupBy = 'grpavg';
          }

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
                (this.series.options.custom.moved ? '' : Highcharts.numberFormat(this.y, 2)) + '</b><br/>';
            }
          };
        });

        // Lock in Y Max/Min
        // if(this.graph_type != 'ave'){
        //   this.yMax = this.yMaxBackup;
        //   this.yMin = this.yMinBackup;
        // }

        // Lock in Y Max/Min
        if (this.graph_type != 'ave') {
          console.log(this.$refs.hchart);
          this.yMax = this.$refs.hchart.chart.yAxis[0].max;
          this.yMin = this.$refs.hchart.chart.yAxis[0].min;
        }

        this.chartTriggerChange();
      }
    },

    disableSeriesDragDrop() {
      if (this.series && this.series.length) {
        console.log("Disabling series DnD");

        // Disable drag
        this.series.forEach((series, idx) => {
          // if(typeof(this.series[idx].dragDrop) === "undefined"){
          this.series[idx].dragDrop = {
            draggableX: false,
            draggableY: false,
            groupBy: this.series[idx].name == 'Average' ? 'grpavg' : ('grp' + (idx + 1))
          };
          // } else {
          //   this.series[idx].dragDrop.draggableX = false;
          //   this.series[idx].dragDrop.draggableY = false;
          // }
          // this.series[idx].dragDrop.draggableX = false;
          // this.series[idx].dragDrop.draggableY = false;

          this.series[idx].pointStart = Date.UTC(2004, 3, 1);
          this.series[idx].pointInterval = 3600 * 1000;
        });

        // Enable labels on yAxis when DnD is false
        this.labelsEnabled = true;

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

    // toggleStacking()
    // {
    //   console.log('Toggle Stacking');
    //   this.plotOptions.series.stacking = this.stacking;
    //   this.chartTriggerChange();
    // },

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
              if (this.series.name == 'Average' && this.status) {
                str += '<span style="color:black;">●</span> Status: <strong>' + this.status + '%</strong><br>';
              }
              return str;
            }
          };
        }
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
        name: 'soil_moisture_edit',
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
    }
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

/* .the_graph .highcharts-container {
  height: 800px !important;
} */

/* .highcharts-background {
  height:800px !important;


} */

/* .highcharts-tooltip-box span:first-child {
  font-size: 15px;
  color: #fff;
  white-space: nowrap;
  white-space: normal !important;
  margin: 4px, 4px;
  padding: 18px;
  background-color: #fff;
  right: 8px !important;
  margin: 0 auto !important;
  top: 0 !important;
  text-align: center;
  border-radius: 2px;
}  */

.highcharts-tooltip-box span:first-child {
  font-size: 15px;
  color: #fff;
  white-space: nowrap;
  white-space: normal !important;
  margin: 4px, 4px;
  padding: 18px;
  background-color: transparent;
  right: 8px !important;
  /* margin: 0 auto!important; */
  top: -18px !important;
  text-align: center;
  border-radius: 2px;
}

/* .highcharts-tooltip-box,
.highcharts-tooltip {
  right: 0 !important;
  margin: 0 auto !important;
  left: 0 !important;
} */

.highcharts-tooltip,
.highcharts-tooltip-box {
  /* right: 0!important; */
  margin: 0 auto !important;
  /* left: 0!important; */
  width: 100%;
  top: 0px !important;
  text-align: center;
}

/* .highcharts-tooltip-box {
  top: 125px !important;
} */

.highcharts-tooltip {
  top: 30px !important;
}

.highcharts-label-box {
  fill: transparent;
}

.date_of_reading {
  color: #333 !important;
  text-align: center !important;
  display: block;
  position: sticky;
  padding: 5px !important;
  border: 0 !important;
  /* font-weight: bold; */
  padding-top: 8px !important;
}

.graph_margin {
  margin-top: -100px;
}

@media only screen and (max-width: 800px) {

  .date_of_reading {
    /* position: absolute !important; */
    text-align: center !important;
    display: block;
    width: 100% !important;
    padding-bottom: 2px !important;
    border-left: 0px solid #666 !important;
    border-top: 1px solid #666 !important;
    border-right: 0px solid #666 !important;
    border-bottom: 0 !important;
    background: transparent;
    margin-bottom: 10px !important;
  }

  .graph_margin {
    margin-top: 0px;
  }

  .highcharts-tooltip-box span:first-child {
    width: 100% !important;
    right: 5px !important;
    left: 0px !important;
    text-align: center;
    padding: 15px;
    width: auto;
    top: 88px !important;
    position: fixed !important;
    border-top: 1px solid #666;
    border-bottom: 1px solid #666;
    background-color: white !important;
  }

  .highcharts-tooltip-box {
    top: 70px !important;
  }
}
</style>
