<template>
  <div class="content">
    <base-header class="pb-6" type="soilmoisture">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show='field_name'>Soil Moisture - {{ field_name }}</h6>
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
                  <base-button :disabled="!node_id || !userCan('Graph', 'Soil Moisture', node_id, 'O') || loading"
                    @click.native="goToGraph()" class="btn" type="primary" size="sm" v-b-tooltip.hover.top
                    title="Navigate to node's graphing screen." icon>
                    Graph
                  </base-button>
                  <base-button :disabled="!cm_id || !userCan('View', 'Cultivars', cm_id, 'O') || loading"
                    @click.native="goToManageCultivars" class="btn" type="primary" size="sm" v-b-tooltip.hover.top
                    title="Manage the node's associated cultivar growth stages" icon>
                    Manage Cultivar
                  </base-button>
                  <b-button v-b-modal.modal-1 size="sm" variant="primary" class="btn" v-b-tooltip.hover.top
                    title="Read More">
                    <b-icon icon="question-circle-fill" aria-label="Help"></b-icon>
                  </b-button>
                  <b-modal id="modal-1" title="Soil Moisture Management">
                    <p>The Soil Moisture Management screen displays the essential information related to a Soil Moisture
                      node.</p>
                    <p>It also allows one to configure agronomic variables that will influence the Soil Moisture Status.
                    </p>
                    <p>The <b>Status</b> calculation is dependent on whether a field has growth stages defined and whether
                      the current date falls within the active growth stage's range (start date + duration).</p>
                    <p><b>Power State: </b> Charging or not (Device Specific)</p>
                    <p><b>Full:</b> The Full value that applies to the field</p>
                    <p><b>Refill:</b> The Refill value that applies to the field</p>
                    <p>Moreover, this screen allows one to set the node's Default Graph Type and Graph Start Date.</p>
                    <p>This screen also provides access to the Node's Soil Moisture Graphing as well as the Cultivar
                      Configuration Screen via the top toolbar buttons.</p>
                  </b-modal>
                </b-col>
              </b-row>
            </template>

            <div class="card-body">
              <b-row>
                <b-col md>
                  <b-toast :visible="true" title="Node Address" static no-auto-hide no-close-button>
                    {{ $route.params.node_address }}
                  </b-toast>
                </b-col>

                <b-col md>
                  <b-toast :visible="true" title="Last Reading" static no-auto-hide no-close-button>
                    {{ date_time }}
                  </b-toast>
                </b-col>

                <b-col md>
                  <b-toast :visible="true" title="Power State" static no-auto-hide no-close-button>
                    {{ power_state }}
                  </b-toast>
                </b-col>

                <b-col md>
                  <b-toast :visible="true" title="Status" static no-auto-hide no-close-button>
                    {{ status }}
                  </b-toast>
                </b-col>
              </b-row>
            </div>
          </card>
        </div>
      </div>

      <div class='row'>
        <div class='col-md-12'>
          <validation-observer ref='form' slim>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">Field Configuration</h3>
              </template>
              <div class="card-body">
                <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="full" :rules="{ required: true, regex: /^-?\d+(\.\d{1,2})?$/ }"
                      label='Full' placeholder="Full" vid="full" v-model="model.full"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input @change="syncFields" name="refill"
                      :rules="{ required: true, regex: /^-?\d+(\.\d{1,2})?$/ }" label='Refill' placeholder="Refill"
                      vid="refill" v-model="model.refill"></base-input>
                  </b-col>
                </b-row>

                <!-- <b-row align-v="center">
                  <b-col md>
                    <base-input @change="syncFields" name="ni" :rules="{ required:true, regex:/^-?\d+(\.\d{1,2})?$/ }" label='NI' placeholder="NI" vid="ni" v-model="model.ni"></base-input>
                  </b-col>
                  <b-col md>
                    <base-input @change="syncFields" name="nr" :rules="{ required:true, regex:/^-?\d+(\.\d{1,2})?$/ }" label='NR' placeholder="NR" vid="nr" v-model="model.nr"></base-input>
                  </b-col>
                </b-row> -->
              </div>
            </card>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">Graph Configuration</h3>
              </template>
              <div class="card-body">
                <b-row align-v="center">
                  <b-col md>
                    <base-input label="Graph Type" name="graph type" rules="required" vid="graph_type">
                      <el-select @change="syncFields" v-model="model.graph_type" filterable placeholder="Graph Type">
                        <el-option label="Seperate Levels" value='sm' selected></el-option>
                        <el-option label="Sum" value='sum'></el-option>
                        <el-option label="Average" value='ave'></el-option>
                        <el-option label="Temperature" value='temp'></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Graph Start Date" name="start date" vid="graph_start_date">
                      <flat-picker slot-scope="{focus, blur}" @on-open="focus" @on-close="blur" @on-change="syncFields"
                        placeholder="Graph Start Date" class="form-control datepicker" v-model="model.graph_start_date"
                        :config="flatPickrConfig">
                      </flat-picker>
                    </base-input>
                  </b-col>
                </b-row>
              </div>
            </card>
          </validation-observer>
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

  mixins: [mab_utils],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    flatPicker
  },

  data() {
    return {
      initial: true,
      loading: false,
      date_now: '',
      model: {
        node_id: '', /* wrongly named in db (Should have been node_address) */
        full: '',
        refill: '',
        // ni: '',
        // nr: '',
        graph_type: '',
        graph_start_date: ''
      },
      email: this.$store.state.email,
      role: this.$store.state.role,
      date_time: '',
      status: '',
      cm_id: '',
      field_id: '',
      field_name: '',
      power_state: ''
    }
  },

  computed: {
    flatPickrConfig: function () {
      return { maxDate: this.date_now }
    }
  },

  methods: {

    loadSoilMoistures() {
      this.loading = true;
      this.$axios.get("/api/ManageSM/" + this.$route.params.node_address)
        .then((resp) => {

          this.loading = false;
          let data = resp.data;

          if (data) {

            if (data.fields) {
              this.model.node_id = data.fields.node_id; /* node_address */
              this.model.full = data.fields.full;
              this.model.refill = data.fields.refill;
              // this.model.ni = data.fields.ni;
              // this.model.nr = data.fields.nr;

              this.status = data.fields.status + '%';
              this.date_time = data.fields.date_time;
              this.cm_id = data.cm_id;
              this.field_id = data.fields.id;
              this.field_name = data.fields.field_name;

              this.node_id = data.node_id; /* actual PK value of node */

              if (this.initial) {
                this.model.graph_type = data.fields.graph_type;
                this.model.graph_start_date = data.fields.graph_start_date;
              }

              // nasty fix for a nasty datepicker
              setTimeout(() => { this.initial = false; }, 1000);
            }

            this.power_state = data.power_state;
            this.date_now = resp.data.date_now;

          }
        });
    },

    syncFields() {
      if (this.initial) return;

      this.$refs.form.validate()
        .then(success => {

          if (!success) {
            this.$notify({
              title: 'Required',
              message: 'Please fill in all fields',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right',
              duration: 1000,
              clean: true
            });
            return;
          }

          this.loading = true;
          this.$axios.post("/api/ManageSave", { model: this.model })
            .then((resp) => {
              this.loading = false;
              if (resp.data.message == 'field_updated') {
                this.$notify({
                  title: 'Saved',
                  message: 'Changes were saved',
                  type: 'success',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });
              } else if (resp.data.message == 'nonexistent') {
                this.$notify({
                  title: 'Error',
                  message: 'Node not found (might have been removed)',
                  type: 'danger',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });
              }

              this.$refs.form.reset();

            }).catch(err => {
              this.loading = false;
              if (err.response.data.errors) {
                this.$refs.form.setErrors(err.response.data.errors);
              }
            });
        });
    },

    goToManageCultivars() {
      this.$router.push({
        name: "cultivars",
        params: { field_id: this.field_id }
      });
    },

    goToGraph() {
      this.$router.push({
        name: "soil_moisture_graph",
        params: { node_address: this.$route.params.node_address }
      });
    }
  },

  created() {
    this.loadSoilMoistures();
  }
};
</script>
<style>
.modal-body {
  padding-top: 0;
}
</style>