<template>
  <div class="content">
    <base-header class="pb-6" type="meters">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Meters - {{ field_name }} - Graph</h6>
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
                  <base-button
                    :disabled="!userCan('Edit', 'Meters', node_id, 'O')"
                    @click.native="goToManage()"
                    class="btn"
                    size="sm"
                    type="primary"
                    v-b-tooltip.hover.top title="Navigate to the meter's management screen"
                    icon>
                    Manage
                  </base-button>
                </div>
              </div>
            </template>

            <div class="card-body">

              <b-row>

                <b-col>
                  <base-input label="Graph Type">
                    <el-select v-model="graph_type" :disabled="loading">
                      <el-option label="Pulse" value='pulse' selected></el-option>
                      <el-option label="Technical" value='tech'></el-option>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col>
                  <base-input label="Range">
                    <el-select 
                      v-b-tooltip.hover.top title="Choose the time range of data to view."
                      v-model="sub_days">
                      <el-option label="Last 24 Hours" value='1'></el-option>
                      <el-option label="Last Week" value='7'></el-option>
                      <el-option label="Last Two Weeks" value='14'></el-option>
                      <el-option label="Last Month" value='31'></el-option>
                      <el-option label="Last Year" value='365'></el-option>
                      <el-option label="Custom" value='custom'></el-option>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col>
                  <base-input v-show="sub_days=='custom'" label="Start Date">
                    <flat-picker 
                      slot-scope="{focus, blur}"
                      @on-open="focus"
                      @on-close="blur"
                      v-model="graph_start_date"
                      :config="{maxDate: date_now }"
                      placeholder="Custom Date"
                      class="form-control datepicker"
                      v-b-tooltip.hover.top title="A custom start date allows a custom range of data to be viewed.">
                    </flat-picker>
                  </base-input>
                </b-col>
              </b-row>

              <highcharts class="hc" :options="chartOptions" :update-args="chartUpdateArgs" ref="hchart"></highcharts>
              
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
import swal from 'sweetalert2';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    flatPicker
  },
  
  data() {
    return {
      loading: false,
      in_progress: true,

      node_id: null,
      field_name: null,

      graph_start_date: '',
      graph_type: '',
      sub_days: '',

      bInitialQuery: true,
      bNotified: false,

      related_nodes: [],

      date_now: '',
      series: [],
      chartUpdateArgs: [ true, true, { duration: 1000 } ],
      seriesAdjusted: false,
      zoomOrDrag: false, /* false == zoom, true == drag */
      lastDate: null,
      exportFileName: '',

      initial_start_date: null,
      initial_end_date: null,

      selection_start: null,
      selection_end:   null,

      yMin: 0,
      yMax: 0,

      xMin: 0,
      xMax: 0,

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
    chartOptions(){
      return {
        credits:{ enabled: false },
        chart: {
          type: 'line',
          zoomType: this.zoomOrDrag == false ? 'x' : false,
          panning: true,
          panKey:'shift',
          height: 600,
          resetZoomButton: { position: { x: 0, y: -50 } }
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
        series: this.series,
        xAxis: {
          min: this.xMin,
          max: this.xMax,
          type: 'datetime',
          tickInterval:1,
          // maxPadding:0,
          dateTimeLabelFormats: {
            millisecond: '%H:%M:%S.%L',
            second: '%H:%M:%S',
            minute: '%H:%M',
            hour: '%H:%M',
            day: '%e %b',
            week: '%e %b',
            month: '%b %Y',
            year: '%Y'
          },
          labels: {
            rotation:-45
          },
          crosshair: true,

          events: {
            setExtremes: function(e){
              var c = this;
              setTimeout(function() {
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
          /* min: this.yMin,
          max: this.yMax, */
          title: { text: '' },
          plotLines: this.plotLines
        },
        legend: {
          layout: 'horizontal',
          align: 'center',
          verticalAlign: 'bottom'
        },
        tooltip: {
          shared: true
        },
        plotOptions: this.plotOptions,
        title: this.chartTitle
      }
    }
  },

  watch: {
    title: function(val){
      this.chartOptions.title = val;
    },
    plotOptions: {
      handler: function(val){
        if(val.length){
          this.chartOptions.plotOptions = val;
        }
      },
      deep: true
    },
    series: {
      handler: function(val){
        if(val.length){
          this.chartOptions.series = val;
        }
      },
      deep: true
    },
    graph_start_date: function(val){
      this.changeDateRange();
    },
    graph_type: function(val){
      this.maybeReloadGraphData();
    },
    sub_days: function(val){
      if(val == 'custom' && !this.graph_start_date){ return; }
      if(val != 'custom'){ this.graph_start_date = ''; }
      // Experimental
      this.initial_start_date = null;
      this.initial_end_date = null;
      this.maybeReloadGraphData();
    }
  },

  methods: {

    loadGraphData() {

      this.loading = true;
      this.in_progress = true;

      this.$refs.hchart.chart.showLoading(this.bInitialQuery ? "Loading..." : "Refreshing...");

      return this.$axios.post("/api/GraphWMV1", {
        node_address: this.$route.params.node_address,
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

        if(resp.data){

          if(resp.data.message && resp.data.message == 'no_data'){
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

          if(!this.bNotified && resp.data.not_current && resp.data.last_date){
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
          this.field_name = resp.data.field_name;
          this.node_id = resp.data.node_id;
          // Experimental
          if(this.bInitialQuery){
            this.graph_start_date = resp.data.graph_start_date;
            this.graph_type = resp.data.graph_type;
            this.sub_days = resp.data.sub_days;
          }

          this.bInitialQuery = false;

          this.xMin = resp.data.x_min;
          this.xMax = resp.data.x_max;
          this.yMin = resp.data.y_min;
          this.yMax = resp.data.y_max;

          this.lastDate = resp.data.last_date.replace(":", '-');
          this.exportFileName = resp.data.graph.field_name + '-' + this.graph_type + '-' + this.$route.params.node_address + '-' + this.lastDate;

          this.plotOptions = resp.data.graph.plotOptions;
          this.chartTitle = resp.data.graph.title;

          this.series = resp.data.graph.series.filter( (series) => { if(series.data){ return series.data.length > 0; } else return false; });
        }
        setTimeout(() => { this.in_progress = false; }, 1);
        return resp;
      });
    },

    maybeReloadGraphData(){
      if(this.in_progress == true){ return; }
      this.loadGraphData();
    },

    changeDateRange(){
      if(this.in_progress == true){ return; }
      if(this.graph_start_date == ''){ return; }
      this.loadGraphData();
    },

    enableSeriesDragDrop(){
      if(this.series && this.series.length){
        
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
            dragMinY:   0,
            dragMaxY:   100,
            groupBy:    'grp'
          };
          // Track changes
          this.series[idx].point = {
            events: {
              dragStart: function(){ 
                this.series.options.custom.moved = true;
              },
            }
          };
          // Format tooltip: Hide value once series was moved
          this.series[idx].tooltip = {
            pointFormatter: function() { 
              return '<span style="color:' + this.series.color + ';">●</span> ' + this.series.name + ': <b>' + 
                (this.series.options.custom.moved ? '' : Highcharts.numberFormat(this.y, 2)) + '</b><br/>';
            }
          };
        });

        this.chartTriggerChange();
      }
    },

    disableSeriesDragDrop(){
      if(this.series && this.series.length){
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

        this.chartTriggerChange();
      }
    },

    toggleSeriesDragDrop(){
      // Zoom
      if(this.zoomOrDrag){
        this.enableSeriesDragDrop();
      // Drag
      } else {
        this.disableSeriesDragDrop();
      }
    },

    showPlotLines(){
      // this.plotLines = [
      //   { /* Full Line */
      //     value: this.full,
      //     color: '#00F',
      //     width: 2,
      //     label: {
      //       x:0,
      //       align:'right',
      //       formatter: () => { return 'Full: ' + this.full; }
      //     }
      //   },
      //   { /* Refill Line */
      //     value: this.refill,
      //     color: '#D26761',
      //     width: 2,
      //     label: {
      //       x:0,
      //       align:'right',
      //       formatter: () => { return 'Refill: ' + this.refill; }
      //     }
      //   }
      // ];
    },

    hidePlotLines(){
      this.plotLines = [];
    },

    // Hack to trigger chart update via faux change
    chartTriggerChange(){
      this.series.push({ type:'line', name: 'Faux', data:[] });
      this.series.pop();
    },

    exportGraphCSV(){

      this.zoomOrDrag = false;

      // accessing internal series data from the outside
      let series = this.$refs.hchart.chart.series;
      try {
        for(var i = 0; i < series.length; i++){
          if(typeof series[i].options.custom.moved !== "undefined"){
            if(series[i].options.custom.moved == true){
              this.seriesAdjusted = true;
              break;
            }
          }
        }
      } catch(err){}

      if(this.seriesAdjusted){
        this.resetSeriesData().then((resp) => {
          this.$refs.hchart.chart.downloadCSV();
        });
      } else {
        this.$refs.hchart.chart.downloadCSV();
      }
    },

    resetSeriesData(){
      this.seriesAdjusted = false;
      return this.loadGraphData();
    },

    goToManage(){
      this.$router.push({
        name: 'meters_edit',
        params: { node_address: this.$route.params.node_address }
      });
    },

    afterSetExtremes(e)
    {
      // initial load
      if(e.trigger == null && this.bInitialQuery){ 
        this.initial_start_date = this.xMin;
        this.initial_end_date = this.xMax;
        this.bInitialQuery = false;
      } else {
        if(e.target.chart.resetZoomButton){
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
    if(this.$route.params.graph_start_date){
      this.sub_days = 'custom';
      this.graph_start_date = this.$route.params.graph_start_date;
    } else {
      this.sub_days = '7';
    }
  },

  mounted() {
    this.loadGraphData();
  }
};
</script>
<style></style>
