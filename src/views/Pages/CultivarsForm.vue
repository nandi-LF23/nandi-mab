<template>
  <div class="content">
    <base-header class="pb-6" type="cultivar">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Soil Moisture - {{ model.field_name }} - Manage Cultivar</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>

      <!-- Browse Cultivar Templates -->
      <b-modal v-model="browseTemplatesModalVisible" centered no-close-on-esc no-close-on-backdrop size='lg'>
        <template #modal-header="{ close }">
          <h6 class="modal-title">Cultivar Templates</h6>
        </template>

        <template #default="{ hide }">
          <b-row class='nomargin'>
            <b-col>
              <b-form-select v-model="perPage">
                <b-form-select-option :value="5">5</b-form-select-option>
                <b-form-select-option :value="10" selected>10</b-form-select-option>
                <b-form-select-option :value="25">25</b-form-select-option>
                <b-form-select-option :value="50">50</b-form-select-option>
              </b-form-select>
            </b-col>
            <b-col md></b-col>
            <b-col> 
              <base-input v-model="filterText"
                prepend-icon="fas fa-search"
                placeholder="Search...">
              </base-input>
            </b-col>
          </b-row>

          <b-row>
            <b-col md>
              <b-table
                striped
                bordered
                outlined
                small
                stacked="md"
                responsive
                show-empty
                primary-key="id"
                :per-page="perPage"
                :current-page="currentPage"
                :filter="filterText"
                :fields="templateTableColumns"
                :items="loadedTemplates"
                :busy.sync="loading">

                <template #cell()="data">{{ data.value }}</template>

                <template #cell(actions)="data">
                  <div class="d-flex justify-content-center">
                    <b-button
                      :disabled="!userCan('Edit','Cultivar Stages', data.item.id, 'O') || loading"
                      @click="activateCultivarTemplate(data.index, data.item)"
                      class="btn"
                      variant="outline-primary"
                      size="sm"
                      icon>
                    Load
                    </b-button>
                    <b-button
                      :disabled="!userCan('Delete', 'Cultivar Templates', data.item.id, 'O') || loading"
                      @click="removeCultivarTemplate(data.index, data.item)"
                      class="btn"
                      variant="outline-primary"
                      size="sm"
                      icon>
                    Remove
                    </b-button>
                  </div>
                </template>

                <template #table-busy>
                  <div class="text-center">
                    <div class='mab_spinner'></div>
                  </div>
                </template>

                <template #empty="scope">
                  <div class="text-center">
                    No templates found
                  </div>
                </template>

              </b-table>
            </b-col>
          </b-row>

          <b-row>
            <b-col md>
              Showing {{ Math.min(1 + (perPage * (currentPage-1)), totalRows) }} to {{ Math.min((perPage*(currentPage-1)) + perPage, totalRows)  }} of {{ totalRows }} templates
            </b-col>
            <b-col md>
              <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
            </b-col>
          </b-row>

        </template>
        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeBrowseTemplatesModal">
            Close
          </b-button>
        </template>
      </b-modal>

      <!-- Save Cultivar Template Dialog -->
      <b-modal v-model="saveTemplatesModalVisible" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 class="modal-title">Save Cultivar Template</h6>
        </template>
        <template #default="{ hide }">
          <validation-observer ref='savetpl' slim>
            <base-input label="Template Name" v-model="saveTemplateName" name="Template Name" rules="required" vid='template_name' placeholder='Template Name'></base-input>
          </validation-observer>
          <b-row>
            <b-col>
            {{ templateSaveStatus }}
            </b-col>
          </b-row>
        </template>
        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeSaveTemplatesModal">
            Close
          </b-button>
          <b-button variant="primary" @click="saveCultivarTemplate" :disabled="!saveTemplateName">
            Save
          </b-button>
        </template>
      </b-modal>

      <!-- The Cultivar Visual Editor Dialog -->
      <b-modal v-model="visualEditorModalVisible" modal-class="modal-fullscreen" centered no-close-on-esc no-close-on-backdrop hide-footer>
        <template #modal-header="{ close }">
          <h6 class="modal-title">{{ visualEditorTitle }}</h6>
          <button aria-label="Close" class='close' @click="confirmVisualEditorClose">×</button>
        </template>

        <template #default="{ hide }">
          
          <b-overlay :show="loading" rounded="sm" variant="white" opacity="0.5" blur="1px">
            <highcharts class="hc" :options="chartOptions" :updateArgs="chartUpdateArgs" ref="hchart"></highcharts>
            <validation-observer ref='plotterstage' slim>
              <b-row align-h="center">

                <b-col cols="6" md="6" lg="3">
                  <b-row>
                    <b-col>
                      <base-input>
                        <el-select @change="visualEditorStageSelected" v-model="selectedStageIndex" placeholder="Stage" :disabled="!stageSeriesData.length">
                          <template v-for="(item, index) in stageSeriesData">
                            <el-option v-if="index < stageSeriesData.length-1" :key="item.index" :label="item.name" :value="item.index"></el-option>
                          </template>
                        </el-select>
                      </base-input>
                    </b-col>
                  </b-row>
                  <b-row>
                    <b-col>
                      <base-input name="stage name" rules="required" vid='stage_name'>
                        <b-input-group prepend="Name">
                          <b-form-input v-model="currentSelectedStage.name" placeholder="Stage Name" :disabled="!stageSeriesData.length"></b-form-input>
                        </b-input-group>
                      </base-input>
                    </b-col>
                  </b-row>
                </b-col>

                <b-col cols="6" md="6" lg="3">
                  <b-row>
                    <b-col>
                      <base-input name="upper" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" vid='stage_upper'>
                        <b-input-group prepend="Upper %" :append="getEffectiveUpper">
                          <b-form-input v-model="currentSelectedStage.upper" placeholder="Upper %" :disabled="!stageSeriesData.length"></b-form-input>
                        </b-input-group>
                      </base-input>
                    </b-col>
                  </b-row>

                  <b-row>
                    <b-col>
                      <base-input name="lower" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" vid='stage_lower'>
                        <b-input-group prepend="Lower %" :append="getEffectiveLower">
                          <b-form-input v-model="currentSelectedStage.lower" placeholder="Lower %" :disabled="!stageSeriesData.length"></b-form-input>
                        </b-input-group>
                      </base-input>
                    </b-col>
                  </b-row>
                </b-col>

                <b-col cols="12" sm="6" md="6" lg="3">
                  <b-row>
                    <b-col cols="6" sm="12" md="12" lg="12">
                      <base-input name="duration" rules="required|integer|min_value:1">
                        <b-input-group prepend="Days">
                          <b-form-input v-model="currentSelectedStage.duration" type="number" min="1" placeholder="Duration" :disabled="!stageSeriesData.length"></b-form-input>
                        </b-input-group>
                      </base-input>
                    </b-col>
                    <b-col cols="6" sm="12" md="12" lg="12">
                      <base-input name="start date" rules="required">
                        <flat-picker
                          slot-scope="{focus, blur}"
                          @on-open="focus"
                          @on-close="blur"
                          :config="{
                            minDate: calcMinDate(currentSelectedStage ? currentSelectedStage.index : null),
                            maxDate: calcMaxDate(currentSelectedStage ? currentSelectedStage.index : null)
                          }"
                          class="form-control datepicker"
                          placeholder="Start Date"
                          :disabled="!stageSeriesData.length"
                          v-model="currentSelectedStage.x">
                        </flat-picker>
                      </base-input>
                    </b-col>
                  </b-row>
                </b-col>

                <b-col cols="12" sm="6" md="6" lg="3" class="text-center text-lg-right text-md-right text-sm-right">
                  <b-row>
                    <b-col cols="6" sm="12" md="12" lg="12">
                      <b-button-group class='mb-lg-4 mb-md-2 mb-sm-2 mb-2'>
                        <b-button variant="outline-primary" @click="visualEditorUpdateStage" :disabled="!stageSeriesData.length || !userCan('Edit', 'Cultivar Stages')">
                          Update
                        </b-button>
                        <b-button variant="primary" @click="applyGraphDataToStages" :disabled="!stageSeriesData.length || !userCan('Edit', 'Cultivar Stages')">
                          Apply
                        </b-button>
                      </b-button-group>
                    </b-col>
                    <b-col cols="6" sm="12" md="12" lg="12">
                      <b-button-group class='mb-lg-4 mb-md-2 mb-sm-2 mb-2'>
                        <b-button variant="outline-primary" @click="showBrowseTemplatesModal" :disabled="!userCan('View', 'Cultivar Templates')">
                          Load Template
                        </b-button>
                        <b-button variant="outline-primary" @click="showSaveTemplatesModal" :disabled="!stageSeriesData.length || !userCan('Add', 'Cultivar Templates')">
                          Save Template
                        </b-button>
                      </b-button-group>
                    </b-col>
                  </b-row>
                </b-col>
              </b-row>
            </validation-observer>

          </b-overlay>
        </template>
      </b-modal>

      <div class='row'>
        <div class='col-md-12'>
          <validation-observer ref='form' slim>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-2">Cultivar Configuration</h3>
                <base-button 
                  :disabled="!userCan('Graph', 'Soil Moisture')"
                  @click.native="goToGraph"
                  class="btn"
                  type="primary"
                  size="sm"
                  icon>
                  Graph
                </base-button>
              </template>
              <div class="card-body">
                <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="cultivar name" rules="required" label='Cultivar Name' placeholder="Cultivar Name" vid="crop_name" v-model="model.crop_name"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Cultivar Type" name="cultivar type" rules="required" vid="crop_type">
                      <el-select @change="syncFields" v-model="model.crop_type" filterable placeholder="Select Crop Type">
                        <el-option label="Permanent" value="Permanent"></el-option>
                        <el-option label="Seasonal" value="Seasonal"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>

                <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="ni" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='NI' placeholder="NI" vid="ni" v-model="model.ni"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input @change="syncFields" name="nr" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='NR' placeholder="NR" vid="nr" v-model="model.nr"></base-input>
                  </b-col>
                </b-row>
                
                <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="full" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='Full' placeholder="100" vid="full" v-model="model.full"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input @change="syncFields" name="refill" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='Refill' placeholder="100" vid="refill" v-model="model.refill"></base-input>
                  </b-col>
                </b-row>
                
                <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="irrigation type" rules="required" label='Irrigation Type' placeholder="Irrigation Type" vid="irrigation_type" v-model="model.irrigation_type"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Graph Model" name="graph model" rules="required" vid="graph_model">
                      <el-select @change="syncFields" v-model="model.graph_model" filterable placeholder="Graph Model">
                        <el-option label="Average" value='ave'></el-option>
                        <el-option label="Sum" value='sum'></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
              </div>
            </card>
          </validation-observer>

          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-2">Cultivar Growth Stages</h3>
              <b-row>
                <b-col md>
                  <base-button @click.native="openVisualEditor()"
                    class="btn"
                    type="primary"
                    size="sm"
                    v-b-tooltip.hover.top title="Manage the cultivar's stages visually."
                    icon>
                    Visual Editor
                  </base-button>
                </b-col>
              </b-row>
            </template>
            <div class="card-body">
              <validation-observer ref='updatestage' slim>
                <b-row align-v="center" v-for="(stage, index) in stages" :key="stage.id">
                  <b-col md>
                    <base-input name="stage name" rules="required" label='Stage Name' v-model="stage.stage_name"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Start Date" name="start date" rules="required" vid="stage_start_date">
                      <flat-picker slot-scope="{focus, blur}"
                        @on-open="focus"
                        @on-close="blur"
                        :config="{
                          minDate: calcMinDate(index),
                          maxDate: calcMaxDate(index)
                        }"
                        class="form-control datepicker"
                        v-model="stage.stage_start_date">
                      </flat-picker>
                    </base-input>
                  </b-col>
                  <b-col md>
                    <base-input name="duration" rules="required|integer" label='Duration' placeholder="" vid="duration" v-model.number="stage.duration"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input name="lower" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='Lower %' placeholder="0" vid="lower" v-model.number="stage.lower"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input name="upper" :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }" label='Upper %' placeholder="100" vid="upper" v-model.number="stage.upper"></base-input>
                  </b-col>
                  <b-col md>
                    <base-button 
                      :disabled="!userCan('Edit', 'Cultivar Stages', stage.id, 'O')"
                      @click.native="updateStage(stage)"
                      class="btn"
                      type="primary"
                      size="sm"
                      v-b-tooltip.hover.top title="Save/update the existing stage's values."
                      icon>
                      Update
                    </base-button>
                  </b-col>
                </b-row>
              </validation-observer>

              <validation-observer ref='addstage' slim>
                <b-row align-v="center">
                  <b-col md>
                    <base-input
                      name="stage name"
                      rules="required"
                      label='Stage Name'
                      vid="stage_name"
                      v-b-tooltip.hover.top title="A descriptive name for the stage."
                      v-model="new_stage.stage_name"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input
                      name="start date"
                      rules="required"
                      label="Start Date"
                      v-b-tooltip.hover.top title="Date at which the stage starts."
                      vid="start_date">
                      <flat-picker slot-scope="{focus, blur}"
                        @on-open="focus"
                        @on-close="blur"
                        :config="{ minDate: calcNewMinDate() }"
                        class="form-control datepicker"
                        v-model="new_stage.start_date">
                      </flat-picker>
                    </base-input>
                  </b-col>
                  <b-col md>
                    <base-input
                      name="duration"
                      rules="required|integer"
                      label='Duration'
                      placeholder=""
                      vid="duration"
                      v-b-tooltip.hover.top title="The stage length (in days)."
                      v-model.number="new_stage.duration"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input
                      name="lower"
                      :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }"
                      label='Lower %'
                      placeholder="0"
                      vid="lower"
                      v-b-tooltip.hover.top title="The stage's lower value."
                      v-model.number="new_stage.lower"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input
                      name="upper"
                      :rules="{ required:true, regex:/^\d+(\.\d{1,2})?$/ }"
                      label='Upper %'
                      placeholder="100"
                      vid="upper"
                      v-b-tooltip.hover.top title="The stage's upper value."
                      v-model.number="new_stage.upper"></base-input>
                  </b-col>
                  <b-col md>
                    <base-button
                      :disabled="!userCan('Add', 'Cultivar Stages')"
                      @click.native="addStage()"
                      class="btn"
                      type="primary"
                      size="sm"
                      v-b-tooltip.hover.top title="Add a new cultivar stage onto the end of the list."
                      icon>
                      Add Stage
                    </base-button>
                  </b-col>
                </b-row>
                <b-row>
                  <b-col md>
                    <base-button
                      :disabled="!userCan('Delete', 'Cultivar Stages')"
                      @click.native="deleteLastStage()"
                      class="btn"
                      type="primary"
                      size="sm"
                      v-b-tooltip.hover.top title="Removes the most recently added cultivar stage"
                      icon>
                    Delete Last Stage
                    </base-button>
                  </b-col>
                </b-row>
              </validation-observer>
            </div>
          </card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import Highcharts from 'highcharts';
import swal from 'sweetalert2';
import { parse, add, sub, format } from 'date-fns';

/* eslint-disable no-new */
import { Select, Option } from 'element-ui'

import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],
  components: {
    flatPicker,
    [Select.name]: Select,
    [Option.name]: Option
  },

  data() {
    return {
      loading: false,
      /* cm */
      model: {

        cm_id: '',
        crop_name: '',
        crop_type: '',
        irrigation_type: '',
        graph_model: 'ave',
        node_address: '',
        
        field_id: this.$route.params.field_id,
        ni: '',
        nr: '',
        full: '',
        refill: '',

        company_id: null
      },

      new_stage: {
        stage_name: 'New Stage',
        start_date: '',
        duration: '',
        lower: 0,
        upper: 100
      },
      
      visualEditorStartDate: null,
      visualEditorModalVisible: false,

      saveTemplatesModalVisible: false,
      browseTemplatesModalVisible: false,
      templateTableColumns: [
        {
          key: 'name',
          label: 'Template',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'created_at',
          label: 'Date Created',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'actions',
          label: 'Actions'
        }
      ],
      loadedTemplates: [],
      loadedTemplateName: '',
      loadedTemplateId: null,
      saveTemplateName: '',
      templateSaveStatus: '',
      templateLoaded: false,

      totalRows: 1,
      currentPage: 1,
      perPage: 10,
      filterText: '',

      stages: [],
      stageSeriesData: [],
      pendingChanges: false,

      // Visual Editor Chart Config
      chartUpdateArgs: [ true, true, { duration: 1000 } ],

      averageSeriesData: [],
      graphYMax: 100,
      graphYMin: 0,

      defaultStage: {
        index: 0,
        name: '',
        lower: 0,
        upper: 100,
        duration: '',
        x: new Date().getTime()
      },
      currentSelectedStage: {
        index: null,
        name: '',
        lower: null,
        upper: null,
        duration: null,
        x: new Date().getTime()
      },
      prevSelectedStage: null,
      nextSelectedStage: null,
      selectedStageIndex: null
    };
  },

  computed: {
    chartOptions(){
      return {
        credits:{ enabled:false },
        chart: {
          zoomType:'x',
          panning:true,
          panKey:'shift'
        },
        series: [
          {
            type: 'areasplinerange',
            name: 'Stages',
            dataLabels: {
              enabled: true,
              formatter: function(){
                // this.point.upper and this.point.lower available
                if(this.y == this.point.high){
                  return Highcharts.numberFormat(this.y,2);
                } else if(this.y == this.point.low){
                  return Highcharts.numberFormat(this.y,2);
                }
              }
            },
            point: {
              events: {
                select: (e) => {
                  let idx = e.target.index;
                  this.selectedStageIndex = idx;

                  //this.currentSelectedStage = Object.assign({}, e.target);
                  //console.log("updating currentSelectedStage from e.target");
                  this.setStageObjectValues(this.currentSelectedStage, e.target);

                  this.prevSelectedStage = typeof this.stageSeriesData[idx-1] !== 'undefined' ? this.stageSeriesData[idx-1] : null;
                  this.nextSelectedStage = typeof this.stageSeriesData[idx+1] !== 'undefined' ? this.stageSeriesData[idx+1] : null;
                },
                unselect: (e) => { return false; }
              }
            },
            allowPointSelect: true,
            cursor: 'pointer',
            data: this.stageSeriesData,
            marker: { states: { select: { fillColor: '#FFA500' } }, radius: 7 },
            fillColor: {
              linearGradient: [0, 0, 0, 300],
              stops: [
                [ 0, Highcharts.getOptions().colors[0] ],
                [ 1, Highcharts.color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba') ]
              ]
            },
          },
          {
            type: 'spline',
            name: 'Average',
            data: this.averageSeriesData,
            color: '#000000'
          },
        ],
        tooltip: {
          formatter: function (tooltip){
            if(this.series){
              if(this.series.name == 'Stages'){
                let tooltip = '<div>';
                tooltip += 'Stage: <strong>' + this.point.name + '</strong><br>';
                tooltip += 'Start: <strong>' + format(new Date(this.point.x), 'yyyy-MM-dd') + '</strong><br>';
                tooltip += 'Duration: <strong>' + this.point.duration + '</strong><br>';
                tooltip += 'Upper %: <strong>' + this.point.upper + '</strong><br>';
                tooltip += 'Lower %: <strong>' + this.point.lower + '</strong><br>';
                tooltip += '</div>';
                return tooltip;
              } else if(this.series.name == 'Average'){
                let tooltip = '<div>';

                let status = 'N/A';

                if(this.point.stage && this.point.model){
                  let volume = this.point.model.full - this.point.model.refill;
                  let upper_value = this.point.model.refill + (volume * this.point.stage.upper / 100);
                  let lower_value = this.point.model.refill + (volume * this.point.stage.lower / 100);
                  status = Highcharts.numberFormat((this.point.y - lower_value) / (upper_value - lower_value) * 100);
                }

                tooltip += Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.point.x) + '<br>';
                tooltip += 'Average: <strong>' + this.point.y + '%</strong><br>';
                tooltip += 'Status: <strong>' + status + '%</strong><br>';
                tooltip += '</div>';
                return tooltip;
              }
            }
            return tooltip.defaultFormatter.call(this, tooltip);
          },
          distance: 50,
          shape: "rect",
        },
        xAxis: {
          title: { text: '' },
          type: 'datetime',
          softMin: this.xAxisSoftMin,
          crosshair: true,
          tickInterval:1
        },
        yAxis: {
          tickInterval:1,
          title: { text: '' },
          min: this.graphYMin,
          max: this.graphYMax,
          minPadding: 0,
          maxPadding: 0,
          plotLines: [
            { /* Full Line */
              value: this.model.full,
              color: '#00F',
              width: 2,
              label: {
                x:0,
                align:'right',
                formatter: () => { return 'Full: ' + this.model.full; }
              }
            },
            { /* Refill Line */
              value: this.model.refill,
              color: '#D26761',
              width: 2,
              label: {
                x:0,
                align:'right',
                formatter: () => { return 'Refill: ' + this.model.refill; }
              }
            }
          ]
        },
        exporting: { enabled: false },
        legend: {
          layout: 'horizontal',
          align: 'center',
          verticalAlign: 'bottom'
        },
        plotOptions: {
          series: {
            keys: [ 'id', 'name', 'x', 'low', 'high', 'lower', 'upper', 'duration', 'stage' ],
            // prevent fading
            states: { inactive: { opacity: 1 } },
            allowPointSelect: false,
            boostThreshold: 0,
            turboThreshold: 0,
            stickyTracking: false
          },
        },
        title: { text: '' }
      };
    },

    visualEditorTitle: function(){
      let title = 'Cultivar Visual Editor';
      if(this.loadedTemplateName){ title += ': ' + this.loadedTemplateName; }
      if(this.pendingChanges){ title += "*"; }
      return title;
    },

    getEffectiveUpper: function(){
      if(this.model && this.currentSelectedStage){
        let volume = this.model.full - this.model.refill;
        return volume ? '(' + Highcharts.numberFormat(this.model.refill + (volume * (this.currentSelectedStage.upper/100)), 2) + ')' : '';
      } else return '';
    },

    getEffectiveLower: function(){
      if(this.model && this.currentSelectedStage){
        let volume = this.model.full - this.model.refill;
        return volume ? '(' + Highcharts.numberFormat(this.model.refill + (volume * (this.currentSelectedStage.lower/100)), 2) + ')' : '';
      } else return '';
    }
  },

  watch: {
    stageSeriesData: {
      handler: function(val){
        this.chartOptions.series[0].data = val;
      },
      deep: true
    },
    averageSeriesData: {
      handler: function(val){
        this.chartOptions.series[1].data = val;
        this.$refs.hchart.chart.redraw();
      },
      deep: true
    },
    graphYMin: {
      handler: function(val){
        this.chartOptions.yAxis.min = parseFloat(val);
      },
      deep: true
    },
    graphYMax: {
      handler: function(val){
        this.chartOptions.yAxis.max = parseFloat(val);
      },
      deep: true
    },

    saveTemplateName: {
      handler: function(val){
        this.templateSaveStatus = this.loadedTemplateName != val ? 'A new template will be created.' : 'Existing template will be updated.';
      }
    },

    visualEditorStartDate: {
      handler: function(val){
        let dt = parse(val, 'yyyy-MM-dd', new Date());
        dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
        this.xAxisSoftMin = dt.getTime();
        this.visualEditorLoadGraphData();
      }
    },
  },

  mounted()
  {
    this.loadCultivarData();
  },

  methods: {

    /* Data loaders */

    /* Loads the main cultivars_management row + stages */
    loadCultivarData()
    {
      this.loading = true;
      this.$axios.get("/api/ManageCultivars/" + this.$route.params.field_id)
      .then((resp) => {
        this.loading = false;
        this.model = resp.data.fields;
        this.stages = resp.data.stages;
        this.setNextStageStartDate();
      });
    },

    /* Load Saved Cultivar Templates */
    loadCultivarStageTemplates()
    {
      this.loading = true;
      this.$axios.get("/api/loadCultiTemplates/" + this.model.company_id)
      .then((resp) => {
        this.loading = false;
        this.loadedTemplates = resp.data.templates;
        this.totalRows = this.loadedTemplates.length;
      });
    },

    /* VISUAL EDITOR OPEN & CLOSE */

    openVisualEditor()
    {
      this.visualEditorModalVisible = true;
      this.visualEditorSetStartDate();
    },

    confirmVisualEditorClose(e){
      if(e){
        e.preventDefault();
      }
      if(this.pendingChanges){
        swal.fire({
          title: 'Close Editor?',
          text: 'Please confirm',
          showCancelButton: true,
          confirmButtonText: 'Close',
          buttonsStyling: false,
          customClass: {
            cancelButton: 'btn btn-outline-primary',
            confirmButton: 'btn btn-primary'
          }
        }).then(result => {
          if (result.value) {
            this.closeVisualEditor();
          }
        });
      } else {
        this.closeVisualEditor();
      }
    },

    closeVisualEditor()
    {
      this.pendingChanges = false;
      this.templateLoaded = false;
      this.loadedTemplateName = '';
      this.stageSeriesData = [];
      this.visualEditorModalVisible = false;
      this.visualEditorStartDate = null;

      //this.currentSelectedStage = Object.assign({}, this.defaultStage);
      //console.log("updating currentSelectedStage from defaultStage");
      this.setStageObjectValues(this.currentSelectedStage, this.defaultStage);

    },

    /* GET GRAPH DATA */
    visualEditorLoadGraphData()
    {
      if(this.visualEditorStartDate){
        this.loading = true;
        this.$axios.post("/api/Graph", {
          unit_of_measure: this.$store.state.user_obj.unit_of_measure,
          type: 'ave',
          node_address: this.model.node_address, /* verified */
          sub_days: 0, /* not used when custom start date is specified */
          custom_start_date: this.visualEditorStartDate
        }).then(resp => {
          this.loading = false;

          // 1. set stages series (if stages exist)
          if(this.stages.length){
            this.visualEditorSetGraphStageData(this.stages);
          }

          // 2. set average series (if data exists) (dependant on 1.)
          if(resp.data){
            this.visualEditorSetAverageStageData(resp.data.graph.series[0].data);
          }

        });
      }
    },

    /* MAB Average Data -> HighCharts */
    visualEditorSetAverageStageData(averageData)
    {
      if(averageData.length && this.stageSeriesData.length){

        this.averageSeriesData = [];

        let yMin = this.model.refill;
        let yMax = this.model.full;

        averageData.forEach((avgItem, index) => {

          let _stage = null;

          this.stageSeriesData.every(stageItem => {
            if(avgItem.x >= stageItem.x && avgItem.x <= (stageItem.x + (stageItem.duration * 24 * 3600 * 1000))){
              _stage = stageItem;
              return false;
            }
            return true;
          });

          averageData[index]['stage'] = _stage;
          averageData[index]['model'] = this.model;

          // calculate minimums/maximums
          yMin = avgItem.y < yMin ? avgItem.y : yMin;
          yMax = avgItem.y > yMax ? avgItem.y : yMax;

          this.averageSeriesData.push(averageData[index]);
        });

        // fit graph
        this.graphYMax = parseFloat(yMax)+1;
        this.graphYMin = parseFloat(yMin)-1;

      }
      return averageData;
    },

    /* MAB Stage Data -> HighCharts */
    visualEditorSetGraphStageData(stageData)
    {
      // stage series data
      if(stageData.length && this.visualEditorStartDate){

        let last_item = stageData[stageData.length-1];
        let dt = parse(this.visualEditorStartDate, 'yyyy-MM-dd', new Date());
        dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
        let xVal = dt.getTime();

        let yMin = 100;
        let yMax = 0;

        let full = parseFloat(this.model.full);
        let refill = parseFloat(this.model.refill);
        let volume = full - refill;

        this.stageSeriesData = [];
        
        stageData.forEach((item, index) => {

          //let low  = parseFloat(full * (item.lower/100));
          //let high = parseFloat(full * (item.upper/100));

          let low  = parseFloat(refill + (volume * (item.lower/100)));
          let high = parseFloat(refill + (volume * (item.upper/100)));

          this.stageSeriesData.push({
            index: index,
            name: item.stage_name,
            x: xVal,
            low: low,
            high: high,
            lower: parseFloat(item.lower),
            upper: parseFloat(item.upper),
            duration: parseInt(item.duration),
            id: item.id,
            selected: false
          });
          xVal += parseInt(item.duration) * 24 * 3600 * 1000;

          // calculate minimums/maximums
          yMin = low < yMin ? low : yMin;
          yMax = high > yMax ? high : yMax;

        });

        // Temporary Point
        this.stageSeriesData.push({
          index: this.stageSeriesData.length, /* Yes, this works, don't add -1 */
          name: last_item.stage_name + ' End',
          x: xVal,
          low:   parseFloat(refill + (volume * (last_item.lower/100))),
          high:  parseFloat(refill + (volume * (last_item.upper/100))),
          lower: parseFloat(last_item.lower),
          upper: parseFloat(last_item.upper),
          duration: 0,
          id: null, /* Not used but added for completeness */
          visible: false,
          selected: false
        });

        // fit graph
        yMin = refill < yMin ? refill : yMin;
        yMax = full > yMax ? full : yMax;

        this.graphYMax = parseFloat(yMax)+1;
        this.graphYMin = parseFloat(yMin)-1;

        // select first stage by default
        this.stageSeriesData[0].selected = true;
        this.selectedStageIndex = 0;

        //this.currentSelectedStage = Object.assign({}, this.stageSeriesData[0]);
        //console.log("updating currentSelectedStage from stageSeriesData[0]");
        this.setStageObjectValues(this.currentSelectedStage, this.stageSeriesData[0]);

      }

      return this.stageSeriesData;
    },

    /* MAB Stage Data <- HighCharts */
    visualEditorGetGraphStageData()
    {
      let series = this.$refs.hchart.chart.series[0];
      let new_stages = [];
      let current_date = this.visualEditorStartDate;

      if(series.data.length > 0 && current_date.length){
        // convert points to MAB stages
        series.data.forEach((item, index) => {
          new_stages.push({
            id: item.id,
            stage_name: item.name,
            stage_start_date: current_date,
            lower: parseFloat(Highcharts.numberFormat(item.lower, 2)),
            upper: parseFloat(Highcharts.numberFormat(item.upper, 2)),
            duration: item.duration
          });
          let dt = parse(current_date, 'yyyy-MM-dd', new Date());
          dt = add(dt, { days: item.duration });
          current_date = format(dt, 'yyyy-MM-dd');
        });
        // discard last stage (informational duration point only)
        new_stages.pop();
      }
      return new_stages;
    },

    /* VISUAL EDITOR CRUD */

    visualEditorStageSelected(idx)
    {
      let series = this.$refs.hchart.chart.series[0];
      if(series.data.length){
        series.data.forEach( item => {
          if(item.index == idx){
            item.select(true, false);
          }
        });
      }
    },

    visualEditorUpdateStage(){
      this.$refs.plotterstage.validate().then(success => {
        if (!success) { return; }

        let yMax = this.graphYMax;
        let yMin = this.graphYMin;

        let full   = parseFloat(this.model.full);
        let refill = parseFloat(this.model.refill);
        let volume = full - refill;
        
        this.stageSeriesData.forEach((item, index) => {
          if(this.currentSelectedStage.index == index){

            this.pendingChanges = true;

            let curr_stage      = this.stageSeriesData[index];
            let prev_stage      = typeof this.stageSeriesData[index-1] !== 'undefined' ? this.stageSeriesData[index-1] : null;
            let next_stage      = typeof this.stageSeriesData[index+1] !== 'undefined' ? this.stageSeriesData[index+1] : curr_stage;

            let curr_x          = new Date(this.currentSelectedStage.x).getTime();
            let old_x           = parseInt(item.x);
            let diff_x          = curr_x-old_x;

            let curr_d          = parseInt(this.currentSelectedStage.duration);
            let old_d           = parseInt(item.duration);
            let diff_d          = curr_d-old_d;

            curr_stage.name     = this.currentSelectedStage.name;
            //curr_stage.high     = parseFloat(full * (this.currentSelectedStage.upper/100));
            //curr_stage.low      = parseFloat(full * (this.currentSelectedStage.lower/100));
            curr_stage.high     = parseFloat(refill + (volume * (this.currentSelectedStage.upper / 100)));
            curr_stage.low      = parseFloat(refill + (volume * (this.currentSelectedStage.lower / 100)));
            curr_stage.upper    = parseFloat(this.currentSelectedStage.upper);
            curr_stage.lower    = parseFloat(this.currentSelectedStage.lower);
            curr_stage.duration = parseInt(this.currentSelectedStage.duration);
            curr_stage.x        = new Date(this.currentSelectedStage.x).getTime();

            // calculate minimums/maximums
            yMin = curr_stage.low < yMin ? curr_stage.low : yMin;
            yMax = curr_stage.high > yMax ? curr_stage.high : yMax;

            // Ensure duration stage (last point) has identical lower/upper/low/high values. 
            if(curr_stage.index == this.stageSeriesData.length-2){
              let last_index = this.stageSeriesData.length-1;
              this.stageSeriesData[last_index].low = curr_stage.low;
              this.stageSeriesData[last_index].high = curr_stage.high;
              this.stageSeriesData[last_index].upper = curr_stage.upper;
              this.stageSeriesData[last_index].lower = curr_stage.lower;
            }
            
            for(var i = 0; i < this.stageSeriesData.length; i++){ this.stageSeriesData[i].selected = false; }
            curr_stage.selected = true;

            // adjust x-values (duration difference)
            if(diff_d){
              diff_d *= 86400000; // (24 * 3600 * 1000)
              for(var i = this.currentSelectedStage.index+1; i < this.stageSeriesData.length; i++){
                this.stageSeriesData[i].x += diff_d;
              }
            }
            // adjust x-values (date difference)
            if(diff_x){
              for(var i = this.currentSelectedStage.index+1; i < this.stageSeriesData.length; i++){
                this.stageSeriesData[i].x += diff_x;
              }
              // adjust previous stage's duration
              if(prev_stage){
                let days = Math.floor(diff_x / 86400000); // (24 / 3600 / 1000)
                prev_stage.duration += days;
              }
            }
          }
        });

        // fit graph
        yMin = refill < yMin ? refill : yMin;
        yMax = full > yMax ? full : yMax;

        this.graphYMax = parseFloat(yMax)+1;
        this.graphYMin = parseFloat(yMin)-1;

      });
    },

    /* LOAD & SAVE CULTIVAR TEMPLATE */

    showBrowseTemplatesModal()
    {
      this.loadCultivarStageTemplates();
      this.browseTemplatesModalVisible = true;
    },

    /* Load a selected template from the list into the chart */
    activateCultivarTemplate(index, item)
    {
      let newStages = null;
      try {
        newStages = JSON.parse(item.template);
      } catch (e) {
        console.log(e.name + ':' + e.message);
      }
      if(newStages){
        this.visualEditorSetGraphStageData(newStages);
        this.closeBrowseTemplatesModal();
        this.templateLoaded = true;
        this.loadedTemplateName = item.name;
        this.loadedTemplateId = item.id;
      }
    },

    removeCultivarTemplate(index, item)
    {
      swal.fire({
        title: 'Remove Template?',
        text: 'Template will be permanently deleted',
        showCancelButton: true,
        confirmButtonText: 'Remove Template',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.loading = true;
          this.$axios.post("/api/removeCultiTemplate", {id: item.id})
          .then((resp) => {
            this.loading = false;
            if(resp.data.status == 'template_removed'){

              /* Update table to reflect deletion */
              let indexToDelete = this.loadedTemplates.findIndex(row => row.id === item.id);
              if (indexToDelete >= 0) { this.loadedTemplates.splice(indexToDelete, 1); }

              this.$notify({
                title: 'Success',
                message: 'Template Removed',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
            }
          });
        }
      });
    },

    closeBrowseTemplatesModal()
    {
      this.browseTemplatesModalVisible = false;
    },

    showSaveTemplatesModal()
    {
      this.saveTemplatesModalVisible = true;
      this.saveTemplateName = this.loadedTemplateName;
    },

    saveCultivarTemplate()
    {
      this.$refs.savetpl.validate().then(success => {
        if (!success) { return; }

        let new_stages = this.visualEditorGetGraphStageData();
        new_stages.forEach( (i) => { i.stage_start_date = null; i.id = null; } );
        this.loading = true;
        this.$axios.post("/api/saveCultiTemplate", { 
          template_name: this.saveTemplateName,
          stages: JSON.stringify(new_stages),
          new: this.saveTemplateName != this.loadedTemplateName ? 'yes' : 'no',
          company_id: this.model.company_id,
          id: this.loadedTemplateId
        }).then((resp) => {
          this.loading = false;
          if(resp.data.status == 'template_saved'){
            this.pendingChanges = false;
            this.closeSaveTemplatesModal();
            // REFRESH TEMPLATES
            this.loadCultivarStageTemplates();
            this.$notify({
              title: 'Success',
              message: 'Template Saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.status == 'template_save_error'){
            this.closeSaveTemplatesModal();
            this.$notify({
              title: 'Failed',
              message: 'Failed to save template. Contact your admin.',
              type: 'warning',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        });
      });
    },

    closeSaveTemplatesModal()
    {
      this.saveTemplatesModalVisible = false;
    },

    /* APPLY */

    applyGraphDataToStages()
    {
      swal.fire({
        title: 'Apply Changes?',
        text: 'Current growth stages will be completely replaced.',
        showCancelButton: true,
        confirmButtonText: 'Apply Changes',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          let new_stages = this.visualEditorGetGraphStageData();
          this.updateOrCreateCultivarStages(new_stages);
          this.closeVisualEditor();
          this.refreshStages();
          this.$notify({
            title: 'Success',
            message: 'Stages Applied',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    /* DB */

    updateOrCreateCultivarStages(stages_data)
    {
      if(stages_data.length){
        let b_new_stages = (this.templateLoaded || this.stages.length == 0) ? 'yes' : 'no';
        let b_replace_stages = (this.templateLoaded && this.stages.length >  0) ? 'yes' : 'no';
        this.loading = true;
        this.$axios.post("/api/stagesSet", { 
          cm_id: this.model.cm_id,
          company_id: this.model.company_id,
          stages: stages_data,
          new: b_new_stages,
          replace: b_replace_stages
        }).then((resp) => {
          this.loading = false;
          // SET NEW START DATE (SERVER SIDE WITH TIMEZONE)
          if(resp.data.status == 'stages_set'){
            // REFRESH DATA
            this.loading = true;
            this.$axios.get("/api/ManageCultivars/" + this.$route.params.field_id)
            .then(resp => {
              this.loading = false;
              this.stages = resp.data.stages;
              this.$notify({
                title: 'Success',
                message: 'Stages Applied',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
            });
          }
        });
      }
    },

    visualEditorSetStartDate()
    {
      this.visualEditorStartDate = 
        this.stages.length && this.stages[0].stage_start_date ?
        this.stages[0].stage_start_date : format(new Date(), 'yyyy-MM-dd');
    },

    calcMinDate(index)
    {
      if(index && typeof this.stages[index] !== 'undefined'){
        // middle stage's min date is it's initial date
        let dt = parse(this.stages[index].stage_start_date, 'yyyy-MM-dd', new Date());
        return dt.getTime();
      } else {
        // first stage has no minimum date
        return null;
      }
    },

    calcMaxDate(index)
    {
      if(index && typeof this.stages[index] !== 'undefined'){
        // last stage has no maximum date
        if(index == this.stages.length-1){ return null; }
        // middle stage's max date is it's next stage's start date
        let dt = parse(this.stages[index+1].stage_start_date, 'yyyy-MM-dd', new Date());
        return dt.getTime();
      } else {
        // first stage has no maximum date
        return null;
      }
    },

    calcNewMinDate(index)
    {
      if(this.stages.length){
        let dt = parse(this.stages[this.stages.length-1].stage_start_date, 'yyyy-MM-dd', new Date());
        dt = add(dt, { days: parseInt(this.stages[this.stages.length-1].duration) });
        return format(dt, 'yyyy-MM-dd');
      } else return null;
    },

    setNextStageStartDate()
    {
      if(this.stages.length){
        let dt = parse(this.stages[this.stages.length-1].stage_start_date, 'yyyy-MM-dd', new Date());
        dt = add(dt, { days: parseInt(this.stages[this.stages.length-1].duration) });
        this.new_stage.start_date = format(dt, 'yyyy-MM-dd');
      } else {
        this.new_stage.start_date = format(new Date(), 'yyyy-MM-dd');
      }
    },

    /* REPEATER CRUD METHODS */

    addStage()
    {
      this.$refs.addstage.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        this.$axios.post("/api/stageAdd", {
          cm_id: this.model.cm_id,
          stage_name: this.new_stage.stage_name,
          stage_start_date: this.new_stage.start_date,
          duration: this.new_stage.duration,
          lower: this.new_stage.lower,
          upper: this.new_stage.upper,
          company_id: this.model.company_id
        }).then((resp) => {
          this.loading = false;
          // SET NEW START DATE (SERVER SIDE WITH TIMEZONE)
          if(resp.data.status == 'stage_added'){
            this.$notify({
              title: 'Success',
              message: 'New stage added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.refreshStages();
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
        });
      });
    },

    updateStage(stage)
    {
      this.$refs.updatestage.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        this.$axios.post("/api/stagesUpdate", {
          stage: stage,
          stages: this.stages,
          company_id: this.model.company_id
        })
        .then((resp) => {
          this.loading = false;
          if(resp.data.status == 'stages_updated'){
            this.$notify({
              title: 'Updated',
              message: 'Stage was updated',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.refreshStages();
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
        });
      });
    },

    deleteLastStage()
    {
      this.loading = true;
      // ensure non-empty stages
      if(this.stages.length){
        this.$axios.post("/api/stageDeleteLast", { cm_id: this.model.cm_id })
        .then(resp => {
          this.loading = false;
          if(resp.data.status == 'stage_deleted'){
            this.$notify({
              title: 'Deleted',
              message: 'Last stage was deleted',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.refreshStages();
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
        });
      }
    },

    refreshStages()
    {
      // REFRESH DATA
      this.loading = true;
      this.$axios.get("/api/ManageCultivars/" + this.$route.params.field_id)
      .then(resp => {
        this.loading = false;
        this.stages = resp.data.stages;
        this.setNextStageStartDate();
      });
    },

    setStageObjectValues(targetObject, sourceObject){
      targetObject.index    = sourceObject.index;
      targetObject.name     = sourceObject.name;
      targetObject.lower    = sourceObject.lower;
      targetObject.upper    = sourceObject.upper;
      targetObject.duration = sourceObject.duration;
      targetObject.x        = sourceObject.x;
      // console.log('    index -> ' + sourceObject.index);
      // console.log('    name  -> ' + sourceObject.name);
      // console.log('    lower -> ' + sourceObject.lower);
      // console.log('    upper -> ' + sourceObject.upper);
      // console.log('    durat -> ' + sourceObject.duration);
      // console.log('    x     -> ' + sourceObject.x);
    },

    syncFields()
    {
      this.$refs.form.validate().then(success => {

        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please fill in all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;
        this.$axios.post("/api/CultivarSave", this.model)
        .then((resp) => {
          this.loading = false;
          if(resp.data && resp.data.message == 'cultivar_updated'){
            this.$notify({
              title: 'Saved',
              message: 'Changes were saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
        });
      });
    },

    goToGraph()
    {
      this.$router.push({
        name: "soil_moisture_graph",
        params:{ node_address: this.model.node_address }
      });
    },

  }
};
</script>
<style>

.modal-fullscreen .modal-dialog {
    max-width: 90%;
    margin: auto;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    height: 100vh;
    display: flex;
    position: fixed;
}

</style>
