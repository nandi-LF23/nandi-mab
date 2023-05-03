<template>
  <div class="content">
    <base-header class="pb-6" type="meters">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show='model.field_name'>Meters - {{ model.field_name }}</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <div class='row'>
        <div class='col-md-12'>
          <card body-classes="px-0 py-0">

            <template slot="header">
              <b-row>
                <b-col>
                  <base-button 
                    :disabled="loading || !userCan('Graph', 'Meters', node_id, 'O')"
                    @click.native="goToGraph()"
                    class="btn"
                    type="primary"
                    size="sm"
                    icon>
                    Graph
                  </base-button>
                </b-col>
              </b-row>
            </template>

            <div class="card-body">
              <b-row>
                <b-col sm="6" md="4">
                  <b-toast :visible="true" title="Node Address" static no-auto-hide no-close-button>
                    {{ node_address }}
                  </b-toast>
                  <b-toast :visible="true" title="Installed" static no-auto-hide no-close-button>
                    {{ model.commissioning_date }}
                  </b-toast>
                  <b-toast :visible="true" title="Last Reading" static no-auto-hide no-close-button>
                    {{ date_time }}
                  </b-toast>
                  <b-toast :visible="true" title="Power State" static no-auto-hide no-close-button>
                    {{ power_state }}
                  </b-toast>
                </b-col>
                <b-col sm="6" md="4">
                  <b-toast :visible="true" title="Pulse 1 Reading (RAW)" static no-auto-hide no-close-button>
                    {{ pulse_reading_1 }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 1 Reading (UOM)" static no-auto-hide no-close-button>
                    {{ pulse_reading_1_uom }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 1 State" static no-auto-hide no-close-button>
                    {{ pulse_state_1 }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 1 mA" static no-auto-hide no-close-button>
                    {{ pulse_ma_1 }}
                  </b-toast>
                </b-col>
                <b-col sm="6" md="4">
                  <b-toast :visible="true" title="Pulse 2 Reading (RAW)" static no-auto-hide no-close-button>
                    {{ pulse_reading_2 }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 2 Reading (UOM)" static no-auto-hide no-close-button>
                    {{ pulse_reading_2_uom }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 2 State" static no-auto-hide no-close-button>
                    {{ pulse_state_2 }}
                  </b-toast>
                  <b-toast :visible="true" title="Pulse 2 mA" static no-auto-hide no-close-button>
                    {{ pulse_ma_2 }}
                  </b-toast>
                </b-col>
                <b-col sm="6" md="4">
                </b-col>
              </b-row>
            </div>
          </card>
        </div>
      </div>

      <div class='row'>
        <div class='col-md-12'>
          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-0">Hardware Config - Advanced Meters</h3>
            </template>

            <div class="card-body">
              <validation-observer ref='form' slim>
                <b-row>
                  <b-col>
                    <base-input @change="syncFields" name="field name" rules="required" label='Field Name' placeholder="Field Name" vid="field_name" v-model="model.field_name"></base-input>
                  </b-col>
                  <b-col>
                    <base-input @change="syncFields" name="device serial number" rules="required" label='Device Serial Number' placeholder="Device Serial Number" vid="device_serial_number" v-model="model.device_serial_number"></base-input>
                  </b-col>
                </b-row>
              </validation-observer>
            </div>
          </card>

          <card body-classes="px-0 py-0">

            <template slot="header">
              <h3 class="mb-0">Graph Configuration</h3>
            </template>

            <div class="card-body">
              <validation-observer ref='nform' slim>
                <b-row align-v="center">
                  <b-col md>
                    <base-input label="Graph Type" name="graph type" rules="required" vid="graph_type">
                      <el-select @change="syncFields" v-model="model.graph_type" filterable placeholder="Graph Type">
                        <el-option label="Pulse" value='pulse'></el-option>
                        <el-option label="Technical" value='tech'></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Graph Start Date" name="start date" vid="graph_start_date">
                      <flat-picker slot-scope="{focus, blur}"
                        @on-open="focus"
                        @on-close="blur"
                        @on-change="syncFields"
                        placeholder="Graph Start Date"
                        class="form-control datepicker"
                        v-model="model.graph_start_date" :config="flatPickrConfig">
                      </flat-picker>
                    </base-input>
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
  /* eslint-disable no-new */
import { Select, Option } from 'element-ui'
import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    flatPicker
  },

  data() {
    return {
      initial: true,
      loading: false,
      date_now: null,
      model: {
        field_name: '',
        device_serial_number: '',
        graph_type: '',
        graph_start_date: ''
      },
      node_id: null,
      node_address: null,
      power_state: '',
      pump_status: false,
      pulse_state_1: 'Unknown',
      pulse_state_2: 'Unknown',
      pulse_ma_1: 0,
      pulse_ma_2: 0,
      date_time: '',
      pulse_reading_1: 0,
      pulse_reading_1_uom: 0,
      pulse_reading_2: 0,
      pulse_reading_2_uom: 0
    };
  },

  computed: {
    flatPickrConfig: function() {
      return { maxDate: this.date_now }
    },
  },

  methods: {

    loadMetersData() {
      this.loading = true;
      this.$axios.get("/api/ManageWMV1/" + this.$route.params.node_address)
      .then(response => {
          this.loading = false;
          console.log(response);
          let data = response.data;
          if(data){

            this.node_id = data.hw.id;
            this.node_address = data.hw.node_address;
            this.date_time = data.hw.date_time;

            this.model.field_name = data.field.field_name;
            this.model.device_serial_number = data.hw.device_serial_number;
            this.model.graph_type = data.field.graph_type;
            this.model.graph_start_date = data.field.graph_start_date;

            if(data.ndm){
              this.pulse_reading_1 = data.ndm.pulse_1;
              this.pulse_reading_1_uom = (data.ndm.pulse_1 * data.hwm.pulse_weight) + ' ' + this.formatUOM(data.hwm.measurement_type);
              this.pulse_reading_2 = data.ndm.pulse_2;
              this.pulse_reading_2_uom = (data.ndm.pulse_2 * data.hwm.pulse_weight) + ' ' + this.formatUOM(data.hwm.measurement_type);
              this.pulse_state_1 = data.ndm.state_of_measure_1 ? 'Open' : 'Closed';
              this.pulse_state_2 = data.ndm.state_of_measure_2 ? 'Open' : 'Closed';
              this.pulse_ma_1 = data.ndm.pulse_1_mA;
              this.pulse_ma_2 = data.ndm.pulse_2_mA;
              this.power_state = this.formatPowerState(data.ndm.power_state);
            }

            if(this.initial){
              this.model.graph_type = data.field.graph_type;
              this.model.graph_start_date = data.field.graph_start_date;
            }

            this.date_now = data.date_now;

            // nasty fix for a nasty datepicker
            setTimeout(() => { this.initial = false; }, 1000);
          }
      }); 
    },

    syncFields(){

      if(this.initial) return;

      this.$refs.form.validate()
      .then(success => {

        if (!success || !this.node_address) { 
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
        this.$axios.post("/api/update_wm/" + this.node_address, this.model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'node_updated'){
            this.$notify({
              title: 'Saved',
              message: 'Changes were saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.message == 'nonexistent'){
            this.$notify({
              title: 'Error',
              message: 'Node not found (might have been removed)',
              type: 'danger',
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

    formatUOM(value){
      if(value == 'Cubes'){ return 'cm3'; }
      if(value == 'Gallons'){ return 'gal'; }
      return '';
    },

    formatPowerState(value){
      let power_states = ['No Power, Not Charging', 'No Power, Charging', 'Power, Not Charging', 'Power, Charging'];
      return (value >= 0 && value < power_states.length) ? power_states[value] : 'Invalid';
    },

    goToGraph()
    {
      this.$router.push({
        name: "meters_graph",
        params:{
          node_address: this.$route.params.node_address
        }
      });
    }

  },
  mounted() {
    this.loadMetersData();
  }
};
</script>
<style scoped>
  .loader {
    position:absolute;
    right:1rem;
    top:1rem;
    z-index:99;
  }
</style>
