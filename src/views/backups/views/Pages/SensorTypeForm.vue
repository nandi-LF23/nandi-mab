<template>
  <div class="content">
    <base-header class="pb-6" type="sensors">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show="model.device_make">Sensor Types - {{
              model.device_make
          }}</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <validation-observer ref='form' slim>
        <b-row>
          <b-col>
            <card body-classes="px-0 py-0">
              <div class="card-body">
                <b-row>
                  <b-col md>
                    <!-- Device Type -->
                    <base-input label="Device Type" name="device type" rules="required" vid="device_type">
                      <el-select @change="model.device_category = ''" v-model="model.device_type" filterable
                        placeholder="Node Type">
                        <el-option label="Soil Moisture" value="Soil Moisture"></el-option>
                        <el-option label="Nutrient" value="Nutrients"></el-option>
                        <el-option label="Well" value="Wells"></el-option>
                        <el-option label="Water Meter" value="Water Meter"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>

                  <b-col md>
                    <!-- Device Category -->
                    <base-input label="Device Category" name="device category" rules="required" vid="device_category">
                      <el-select @change="syncFields" v-model="model.device_category" filterable
                        placeholder="Device Category">
                        <template v-if="model.device_type == 'Wells' || model.device_type == 'Water Meter'">
                          <el-option label='Mechanical' value='Mechanical'>Mechanical</el-option>
                          <el-option label='MagFlo' value='MagFlo'>MagFlo</el-option>
                          <el-option label='Ultrasonic' value='Ultrasonic'>Ultrasonic</el-option>
                        </template>
                        <template v-if="model.device_type == 'Soil Moisture'">
                          <el-option label="Soil Moisture Probe" value="Soil Moisture Probe"></el-option>
                        </template>
                        <template v-if="model.device_type == 'Nutrients'">
                          <el-option label="Nutrient Probe" value="Nutrient Probe"></el-option>
                        </template>
                      </el-select>
                    </base-input>
                  </b-col>

                  <b-col md>
                    <!-- Entity (Read Only) -->
                    <base-input label="Entity" name="company" rules="required" vid="role" v-model="sensor_company_name"
                      disabled>
                    </base-input>
                  </b-col>
                </b-row>
                <b-row>
                  <b-col md>
                    <!-- Device Make (Name) -->
                    <base-input @change="syncFields" name="device make" rules="required" label="Device Make"
                      placeholder="Device Make" vid="device_make" v-model="model.device_make">
                    </base-input>
                  </b-col>
                </b-row>
              </div>
            </card>
          </b-col>
        </b-row>

        <b-row v-if="model.device_type == 'Soil Moisture'">
          <b-col>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">{{ model.device_type }} Device Configuration</h3>
              </template>

              <div class="card-body">

                <b-row>
                  <b-col md>
                    <!-- Device Length -->
                    <base-input label="Device Length" name="device length" rules="required" vid="device_length">
                      <b-form-select @change="syncFields" v-model="model.device_length" placeholder="Device Length*">
                        <b-form-select-option value='200mm'>{{ uom == 1 ? '200mm' : '8"' }}</b-form-select-option>
                        <b-form-select-option value='300mm'>{{ uom == 1 ? '300mm' : '12"' }}</b-form-select-option>
                        <b-form-select-option value='400mm'>{{ uom == 1 ? '400mm' : '16"' }}</b-form-select-option>
                        <b-form-select-option value='500mm'>{{ uom == 1 ? '500mm' : '20"' }}</b-form-select-option>
                        <b-form-select-option value='600mm'>{{ uom == 1 ? '600mm' : '24"' }}</b-form-select-option>
                        <b-form-select-option value='700mm'>{{ uom == 1 ? '700mm' : '28"' }}</b-form-select-option>
                        <b-form-select-option value='800mm'>{{ uom == 1 ? '800mm' : '32"' }}</b-form-select-option>
                        <b-form-select-option value='900mm'>{{ uom == 1 ? '900mm' : '36"' }}</b-form-select-option>
                        <b-form-select-option value='1000mm'>{{ uom == 1 ? '1000mm' : '40"' }}</b-form-select-option>
                        <b-form-select-option value='1100mm'>{{ uom == 1 ? '1100mm' : '44"' }}</b-form-select-option>
                        <b-form-select-option value='1200mm'>{{ uom == 1 ? '1200mm' : '48"' }}</b-form-select-option>
                        <b-form-select-option value='1300mm'>{{ uom == 1 ? '1300mm' : '52"' }}</b-form-select-option>
                        <b-form-select-option value='1400mm'>{{ uom == 1 ? '1400mm' : '56"' }}</b-form-select-option>
                        <b-form-select-option value='1500mm'>{{ uom == 1 ? '1500mm' : '60"' }}</b-form-select-option>
                      </b-form-select>
                    </base-input>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col md>
                    <label class='form-control-label'>Sensor Depths</label>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_1"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '100mm' : '4"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_2"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '200mm' : '8"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_3"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '300mm' : '12"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_4"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '400mm' : '16"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_5"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '500mm' : '20"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_6"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '600mm' : '24"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_7"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '700mm' : '28"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_8"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '800mm' : '32"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_9"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '900mm' : '36"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_10"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1000mm' : '40"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_11"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1100mm' : '44"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_12"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1200mm' : '48"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_13"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1300mm' : '52"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_14"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1400mm' : '56"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" @change="syncFields" v-model="model.sensor_placing_15"
                      unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1500mm' : '60"' }}
                    </b-form-checkbox>
                  </b-col>
                </b-row>
              </div>
            </card>
          </b-col>
        </b-row>

        <b-row v-if="model.device_type == 'Wells' || model.device_type == 'Water Meter'">
          <b-col>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">{{ model.device_type }} Device Configuration</h3>
              </template>
              <div class="card-body">
                <b-row>
                  <b-col md>
                    <base-input @change="syncFields" label="Diameter" name="device diameter" rules="required|numeric"
                      placeholder="Device Diameter" vid="diameter" v-model="model.diameter">
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="pulse weight" rules="required" label='Pulse Weight' vid="pulse_weight">
                      <el-select @change="syncFields" v-model="model.pulse_weight" filterable
                        placeholder="Pulse Weight">
                        <el-option label="1" value="1"></el-option>
                        <el-option label="10" value="10"></el-option>
                        <el-option label="100" value="100"></el-option>
                        <el-option label="1000" value="1000"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col>
                    <base-input name="measurement_type" rules="required" label="Unit of Measure" vid="measurement_type">
                      <el-select @change="syncFields" v-model="model.measurement_type" filterable
                        placeholder="Unit Of Measure">
                        <el-option label="Gallons" value="gallons"></el-option>
                        <el-option label="Cubes" value="cubes"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="application type" rules="required" label="Application Type"
                      vid="application_type">
                      <el-select @change="syncFields" v-model="model.application_type" filterable
                        placeholder="Application Type">
                        <el-option label="GPM" value="gpm"></el-option>
                        <el-option label="GPH" value="gph"></el-option>
                        <el-option label="Acre Inches" value="acre_inches"></el-option>
                        <el-option label="Acre Feet" value="acre_feet"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
              </div>
            </card>
          </b-col>
        </b-row>
      </validation-observer>

      <validation-observer ref='nutform' slim>
        <b-row>
          <b-col v-if="model.device_type == 'Nutrients'">
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">{{ model.device_type }} Device Configuration</h3>
              </template>

              <div class="card-body">

                <b-row>
                  <b-col md="6">

                    <base-input label="Device Length" name="device length" rules="required" vid="device_length">
                      <b-form-select @change="syncFields" v-model="model.device_length" placeholder="Device Length*">
                        <b-form-select-option value='200mm'>{{ uom == 1 ? '200mm' : '8"' }}</b-form-select-option>
                        <b-form-select-option value='400mm'>{{ uom == 1 ? '400mm' : '16"' }}</b-form-select-option>
                        <b-form-select-option value='600mm'>{{ uom == 1 ? '600mm' : '24"' }}</b-form-select-option>
                        <b-form-select-option value='800mm'>{{ uom == 1 ? '800mm' : '32"' }}</b-form-select-option>
                      </b-form-select>
                    </base-input>

                  </b-col>

                  <b-col md="6">
                  
                    <base-input label="Number of Levels" name="Number of Levels" rules="required" vid="number_levels">
                      <b-form-select @change="syncFields" v-model="model.number_levels" placeholder="Number of Levels*">
                        <b-form-select-option value='1'>1</b-form-select-option>
                        <b-form-select-option value='2'>2</b-form-select-option>
                        <b-form-select-option value='3'>3</b-form-select-option>
                        <b-form-select-option value='4'>4</b-form-select-option>
                      </b-form-select>
                    </base-input>
                  
                  </b-col>
                </b-row>

                <b-row>

                  <b-col md="12">
                    <label class='form-control-label'>Sensor Config</label>
                    <b-table-simple small class='mab_table'>
                      <b-tbody>
                        <b-tr v-for="(config, index) in model.sensor_config" :key="index">
                          <b-td class="mab_table_cell">
                            <base-input name="sdi_addr" rules="required" vid="address">
                              <el-select @change="syncFields" v-model="config.address" filterable
                                placeholder="SDI Addr." autocomplete="off" size="small">
                                <el-option v-for="(val, idx) in sensor_addresses" :key="idx" :label="idx"
                                  :value="val">{{ idx }}</el-option>
                              </el-select>
                            </base-input>
                          </b-td>
                          <!-- <b-td class="mab_table_cell">
                            <base-input name="depth" rules="required" vid="depth">
                              <el-select @change="syncDepths" v-model="config.depth" filterable placeholder="Depth"
                                size="small">
                                <el-option v-for="(item, idx) in sensor_depths" :key="'e' + idx"
                                  :label="uom == 1 ? item : convertToInches(item)" :value="item">
                                  {{ uom == 1 ? item : convertToInches(item) }}
                                </el-option>
                              </el-select>
                            </base-input>
                          </b-td> -->
                          <b-td class="mab_table_cell">
                            <base-input name="type" rules="required" vid="type">
                              <el-select @change="syncFields" v-model="config.type" filterable placeholder="Type"
                                size="small">
                                <el-option v-for="(item, idx) in sensor_types" :key="idx" :label="item"
                                  :value="item"></el-option>
                              </el-select>
                            </base-input>
                          </b-td>
                          <b-td class="mab_table_cell">
                            <base-input>
                              <base-button @click="removeSensorConfigRow(index)" class="btn" type="primary" size="sm"
                                icon>
                                Rem
                              </base-button>
                            </base-input>
                          </b-td>
                        </b-tr>
                      </b-tbody>
                    </b-table-simple>

                    <validation-observer ref='nutaddform' v-slot="{ validate }" slim>
                      <b-table-simple small class='mab_table'>
                        <b-tbody>
                          <b-tr>
                            <b-td class="mab_table_cell">
                              <base-input name="sdi_addr" rules="required" vid="address">
                                <el-select v-model="sensor_model.address" filterable placeholder="SDI Addr."
                                  autocomplete="off" size="small">
                                  <el-option v-for="(val, idx) in sensor_addresses" :key="idx" :label="idx"
                                    :value="val">{{ idx }}</el-option>
                                </el-select>
                              </base-input>
                            </b-td>
                            <!-- <b-td class="mab_table_cell">
                              <base-input name="depth" rules="required" vid="depth">
                                <el-select v-model="sensor_model.depth" filterable placeholder="Depth" size="small">
                                  <el-option v-for="(item, idx) in sensor_depths" :key="'a' + idx"
                                    :label="uom == 1 ? item : convertToInches(item)" :value="item">
                                    {{ uom == 1 ? item : convertToInches(item) }}
                                  </el-option>
                                </el-select>
                              </base-input>
                            </b-td> -->
                            <b-td class="mab_table_cell">
                              <base-input name="type" rules="required" vid="type">
                                <el-select v-model="sensor_model.type" filterable placeholder="Type" size="small">
                                  <el-option v-for="(item, idx) in sensor_types" :key="idx" :label="item"
                                    :value="item"></el-option>
                                </el-select>
                              </base-input>
                            </b-td>
                            <b-td class="mab_table_cell">
                              <base-input>
                                <base-button @click="addSensorConfigRow(validate)" class="btn" type="primary" size="sm"
                                  icon>
                                  Add
                                </base-button>
                              </base-input>
                            </b-td>
                          </b-tr>
                        </b-tbody>
                      </b-table-simple>
                    </validation-observer>
                  </b-col>
                </b-row>
              </div>
            </card>
          </b-col>
        </b-row>
      </validation-observer>
    </div>
  </div>
</template>
<script>
import { Select, Option } from 'element-ui'
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [mab_utils],

  components: {
    [Select.name]: Select,
    [Option.name]: Option
  },

  data() {
    return {
      loading: false,
      model: {
        id: "",
        device_type: "",
        device_make: "",
        device_length: "",
        device_category: "",
        diameter: "",
        sensor_placing_1: "off",
        sensor_placing_2: "off",
        sensor_placing_3: "off",
        sensor_placing_4: "off",
        sensor_placing_5: "off",
        sensor_placing_6: "off",
        sensor_placing_7: "off",
        sensor_placing_8: "off",
        sensor_placing_9: "off",
        sensor_placing_10: "off",
        sensor_placing_11: "off",
        sensor_placing_12: "off",
        sensor_placing_13: "off",
        sensor_placing_14: "off",
        sensor_placing_15: "off",
        sensor_config: [],
        company_id: null
      },

      sensor_model: {
        address: '',
        depth: '',
        type: ''
      },

      sensor_addresses: {

        'M0.1': 'M0_1',
        'M1.1': 'M1_1',
        'M2.1': 'M2_1',
        'M3.1': 'M3_1',
        'M4.1': 'M4_1',
        'M5.1': 'M5_1',
        'M6.1': 'M6_1',
        'M7.1': 'M7_1',
        'M8.1': 'M8_1',
        'M9.1': 'M9_1',

        'M0.2': 'M0_2',
        'M1.2': 'M1_2',
        'M2.2': 'M2_2',
        'M3.2': 'M3_2',
        'M4.2': 'M4_2',
        'M5.2': 'M5_2',
        'M6.2': 'M6_2',
        'M7.2': 'M7_2',
        'M8.2': 'M8_2',
        'M9.2': 'M9_2',

        'M0.3': 'M0_3',
        'M1.3': 'M1_3',
        'M2.3': 'M2_3',
        'M3.3': 'M3_3',
        'M4.3': 'M4_3',
        'M5.3': 'M5_3',
        'M6.3': 'M6_3',
        'M7.3': 'M7_3',
        'M8.3': 'M8_3',
        'M9.3': 'M9_3',

        'M0.4': 'M0_4',
        'M1.4': 'M1_4',
        'M2.4': 'M2_4',
        'M3.4': 'M3_4',
        'M4.4': 'M4_4',
        'M5.4': 'M5_4',
        'M6.4': 'M6_4',
        'M7.4': 'M7_4',
        'M8.4': 'M8_4',
        'M9.4': 'M9_4'

      },

      sensor_depths: [
        '100mm', '200mm', '300mm', '400mm', '500mm', '600mm', '700mm', '800mm', '900mm', '1000mm', '1100mm', '1200mm', '1300mm', '1400mm', '1500mm'
      ],

      sensor_types: [
        'Soil Moisture',
        'Temperature',
        'E.C',
        'NO3',
        'Nutrient 2',
        'Nutrient 3',
        'Nutrient 4'
      ],

      companies: [],
      uom: this.$store.getters.getUOM,
      prevDepth: null,

      sensor_company_name: ''
    };
  },
  methods: {
    loadHardware() {
      this.loading = true;
      this.$axios.get("/api/hardwaremanagementform/" + this.$route.params.id)
        .then((response) => {
          this.loading = false;
          this.model = response.data.hardware;
          this.loadCompanies();
        });
    },

    loadCompanies() {
      this.$axios.post("/api/companies_list", {
        context: [{ verb: 'Edit', module: 'Sensor Types' }]
      }).then((resp) => {
        this.companies = resp.data.companies;
        this.sensor_company_name = this.companies['"' + this.model.company_id.toString() + '"'].company_name;
      });
    },

    addSensorConfigRow(validate) {
      validate().then((success) => {
        if (!success) { return; }

        if (this.model.sensor_config === null) {
          this.model.sensor_config = [];
        }

        this.model.sensor_config.push(JSON.parse(JSON.stringify(this.sensor_model)));

        let num = parseInt(parseInt(this.sensor_model.depth.replace(/mm/g, '')) / 100);
        this.model['sensor_placing_' + num] = true;
        // clear model

        this.sensor_model.address = '';
        this.sensor_model.depth = '';
        this.sensor_model.type = '';

        this.$refs["nutaddform"].reset();

        console.log(this.model.sensor_config);

        this.syncDepths();

      });
    },

    removeSensorConfigRow(index) {
      this.model.sensor_config.splice(index, 1);
      this.syncDepths();
    },

    syncDepths() {
      for (var i = 1; i <= 15; i++) {
        this.model['sensor_placing_' + i] = 'off';
      }
      this.model.sensor_config.forEach((v, i) => {
        var index = this.convertToIndex(v.depth);
        this.model['sensor_placing_' + index] = 'on';
      });
      this.syncFields();
    },

    syncFields() {
      return this.$refs.form.validate().then(success => {

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
        return this.$axios.post("/api/hardwaremanagementsave", this.model)
          .then((response) => {
            this.loading = false;

            if (response.data.message == 'sensor_updated') {

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
            if (err.response.data.errors) {
              this.$refs.form.setErrors(err.response.data.errors);
            }
            return err;
          });
      });
    },
  },
  mounted() {
    this.loadHardware();
  }
};
</script>
<style>

</style>
