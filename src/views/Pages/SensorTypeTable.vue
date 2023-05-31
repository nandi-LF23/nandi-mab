<template>
  <div class="content">
    <base-header class="pb-6" type="sensors">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Sensor Types</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Clone Sensor -->
      <b-modal v-model="showCloneSensorModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Clone Sensor</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='clone_sensor_form' slim>
            <form role="form" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Target Entity" name="company" rules="required" vid="company" placeholder="Entity"
                      autocomplete="off">
                      <el-select :disabled="Object.values(companies).length < 2" @change="companyChanged"
                        v-model="clone_model.company_id" filterable placeholder="Entity" autocomplete="off">

                        <template v-for="company in companies">
                          <el-option :key="company.id" :label="company.company_name" :value="company.id">
                            <span>{{ company.company_name }}</span>
                          </el-option>
                        </template>

                      </el-select>
                    </base-input>
                  </div>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeCloneSensorModal">Cancel</b-button>
          <b-button variant="primary" @click='cloneSensor'>Clone</b-button>
        </template>

      </b-modal>

      <!-- Add New Sensor Dialog -->
      <b-modal dialog-class='mab_modal' v-model="showNewSensorTypeModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Add New Sensor Type</h6>
        </template>

        <template #default="{ hide }">

          <el-steps :active="formStepIndex" finish-status="success" align-center>
            <el-step title="Device Details"></el-step>
            <el-step title="Configuration"></el-step>
            <el-step title="Confirmation"></el-step>
          </el-steps>

          <validation-observer ref='addstep_detail' slim>
            <form role="addstep_detail" v-show="formStep == 'detail'" autocomplete="off" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <h3 class="mb-2">Device Details</h3>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Device Make (Name) -->
                  <base-input label="" name="device make" rules="required|max:128" vid="device_make"
                    placeholder="Device Make" v-model="model.device_make">
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Device Type -->
                  <base-input label="" name="device type" rules="required" vid="device_type">
                    <el-select v-model="model.device_type" @change="model.device_category = ''" filterable
                      placeholder="Device Type">
                      <el-option :disabled="!userCan('Add', 'Soil Moisture')" label="Soil Moisture"
                        value="Soil Moisture"></el-option>
                      <el-option :disabled="!userCan('Add', 'Nutrients')" label="Nutrient" value="Nutrients"
                        v-if="isAdmin()"></el-option>
                      <el-option :disabled="!userCan('Add', 'Well Controls')" label="Well Controls"
                        value="Wells"></el-option>
                      <el-option :disabled="!userCan('Add', 'Meters')" label="Meters" value="Water Meter"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <!-- Device Category -->
                  <base-input label="" name="device category" rules="required" vid="device_category">
                    <el-select v-model="model.device_category" filterable placeholder="Device Category">
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
              </b-row>

              <b-row>
                <b-col>
                  <!-- Entity/Company -->
                  <base-input name="company" rules="required" vid="company" autocomplete="off">
                    <el-select @change="loadGroups" v-model="model.company_id" filterable placeholder="Entity"
                      :disabled="!multipleCompanies" autocomplete="nope">
                      <el-option v-for="company in companies" :key="company.id" :label="company.company_name"
                        :value="company.id"></el-option>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col>
                  <!-- Device Type Group (Optional)-->
                  <base-input name="group" vid="group" :disabled="!model.company_id || !groups">
                    <el-select v-model="model.group_id" filterable placeholder="Group (Optional)"
                      :disabled="!model.company_id || !groups">
                      <el-option-group label="Device Type Groups">
                        <el-option v-for="group in groups" :key="group.id" :label="group.group_name"
                          :value="group.id"></el-option>
                      </el-option-group>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>

            </form>
          </validation-observer>

          <validation-observer ref='addstep_config' slim>
            <form role="addstep_config" v-show="formStep == 'config'" autocomplete="off" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <h3 class="mb-2">{{ model.device_type }} Device Configuration</h3>
                </b-col>
              </b-row>

              <template v-if="model.device_type == 'Soil Moisture' || model.device_type == 'Nutrients'">
                <b-row>
                  <b-col md>
                    <base-input label="" name="device length" rules="required" vid="device_length">
                      <el-select v-model="model.device_length" filterable placeholder="Probe Length">
                        <el-option v-for="(item, idx) in sensor_depths" :key="'e' + idx"
                          :label="uom == 1 ? item : convertToInches(item)" :value="item">
                          {{ uom == 1 ? item : convertToInches(item) }}
                        </el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
              </template>

              <template v-if="model.device_type == 'Soil Moisture'">

                <b-row>
                  <b-col md>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_1" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '100mm' : '4"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_2" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '200mm' : '8"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_3" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '300mm' : '12"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_4" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '400mm' : '16"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_5" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '500mm' : '20"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_6" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '600mm' : '24"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_7" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '700mm' : '28"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_8" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '800mm' : '32"' }}
                    </b-form-checkbox>

                  </b-col>
                  <b-col md>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_9" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '900mm' : '36"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_10" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1000mm' : '40"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_11" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1100mm' : '44"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_12" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1200mm' : '48"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_13" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1300mm' : '52"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_14" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1400mm' : '56"' }}
                    </b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.sensor_placing_15" unchecked-value="off" value="on">
                      Sensor at {{ uom == 1 ? '1500mm' : '60"' }}
                    </b-form-checkbox>
                  </b-col>
                </b-row>
              </template>

              <template v-if="model.device_type == 'Nutrients'">

                <b-table-simple small class='mab_table'>
                  <b-tbody>
                    <b-tr v-for="(config, index) in model.sensor_config" :key="index">
                      <b-td class="mab_table_cell">
                        <base-input name="sdi_addr" rules="required" vid="address">
                          <el-select v-model="config.address" filterable placeholder="SDI Address" autocomplete="off">
                            <el-option v-for="(val, idx) in sensor_addresses" :key="idx" :label="idx"
                              :value="val">{{ idx }}</el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input name="depth" rules="required" vid="depth">
                          <el-select v-model="config.depth" filterable placeholder="Depth">
                            <el-option v-for="(item, idx) in sensor_depths" :key="idx" :label="item"
                              :value="item">{{ item }}</el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input name="type" rules="required" vid="type">
                          <el-select v-model="config.type" filterable placeholder="Type">
                            <el-option v-for="(item, idx) in sensor_types" :key="idx" :label="item"
                              :value="item"></el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input>
                          <base-button @click="removeSensorConfigRow(index)" class="btn" type="primary" size="sm" icon>
                            Rem
                          </base-button>
                        </base-input>
                      </b-td>
                    </b-tr>
                  </b-tbody>
                </b-table-simple>

                <b-table-simple small class='mab_table'>
                  <b-tbody>
                    <b-tr>
                      <b-td class="mab_table_cell">
                        <base-input name="sdi_addr" rules="required" vid="address">
                          <el-select v-model="sensor_model.address" filterable placeholder="SDI Address"
                            autocomplete="nope">
                            <el-option v-for="(val, idx) in sensor_addresses" :key="idx" :label="idx"
                              :value="val">{{ idx }}</el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input name="depth" rules="required" vid="depth">
                          <el-select v-model="sensor_model.depth" filterable placeholder="Depth">
                            <el-option v-for="(item, idx) in sensor_depths" :key="'a' + idx"
                              :label="uom == 1 ? item : convertToInches(item)" :value="item">
                              {{ uom == 1 ? item : convertToInches(item) }}
                            </el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input name="type" rules="required" vid="type">
                          <el-select v-model="sensor_model.type" filterable placeholder="Type">
                            <el-option v-for="(item, idx) in sensor_types" :key="idx" :label="item"
                              :value="item"></el-option>
                          </el-select>
                        </base-input>
                      </b-td>
                      <b-td class="mab_table_cell">
                        <base-input>
                          <base-button @click="addSensorConfigRow" class="btn" type="primary" size="sm" icon>
                            Add
                          </base-button>
                        </base-input>
                      </b-td>
                    </b-tr>
                  </b-tbody>
                </b-table-simple>

              </template>

              <template v-else-if="model.device_type == 'Wells' || model.device_type == 'Water Meter'">
                <b-row>
                  <b-col md>
                    <base-input label="Diameter" name="device diameter" rules="required|numeric"
                      placeholder="Device Diameter" vid="diameter" v-model="model.diameter">
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="pulse weight" rules="required" label='Pulse Weight' vid="pulse_weight">
                      <el-select v-model="model.pulse_weight" filterable placeholder="Pulse Weight">
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
                      <el-select v-model="model.measurement_type" filterable placeholder="Unit Of Measure">
                        <el-option label="Gallons" value="gallons"></el-option>
                        <el-option label="Cubes" value="cubes"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="application type" rules="required" label="Application Type" vid="application_type">
                      <el-select v-model="model.application_type" filterable placeholder="Application Type">
                        <el-option label="GPM" value="gpm"></el-option>
                        <el-option label="GPH" value="gph"></el-option>
                        <el-option label="Acre Inches" value="acre_inches"></el-option>
                        <el-option label="Acre Feet" value="acre_feet"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>

              </template>
            </form>
          </validation-observer>

          <b-row v-show="formStep == 'confirm'">
            <b-col class='confirm'>
              <h3>Confirmation</h3>

              <b-row>
                <b-col>
                  <b-table-simple responsive small fixed>
                    <b-tr v-if="model.company_id != $store.state.user_obj.company_id">
                      <b-td><strong>Company</strong></b-td><b-td>{{
                        companies['"' + model.company_id.toString() + '"'].company_name }}</b-td>
                    </b-tr>
                    <b-tr><b-td><strong>Make</strong></b-td><b-td>{{ model.device_make }}</b-td></b-tr>
                    <b-tr><b-td><strong>Device Type</strong></b-td><b-td>{{ model.device_type }}</b-td></b-tr>
                    <b-tr><b-td><strong>Category</strong></b-td><b-td>{{ model.device_category }}</b-td></b-tr>
                    <template v-if="model.device_type == 'Wells' || model.device_type == 'Water Meter'">
                      <b-tr><b-td><strong>Diameter</strong></b-td><b-td>{{ model.diameter }}</b-td></b-tr>
                      <b-tr><b-td><strong>Pulse Weight</strong></b-td><b-td>{{ model.pulse_weight }}</b-td></b-tr>
                      <b-tr><b-td><strong>Measurement Type</strong></b-td><b-td>{{ model.measurement_type }}</b-td></b-tr>
                      <b-tr><b-td><strong>Application Type</strong></b-td><b-td>{{ model.application_type }}</b-td></b-tr>
                    </template>
                    <template v-if="model.device_type == 'Soil Moisture' || model.device_type == 'Nutrients'">
                      <b-tr>
                        <b-td><strong>Length</strong></b-td>
                        <b-td>
                          {{ uom == 1 ? model.device_length : convertToInches(model.device_length) }}
                        </b-td>
                      </b-tr>
                    </template>
                  </b-table-simple>

                  <b-table-simple responsive small fixed v-if="model.device_type == 'Soil Moisture'">
                    <b-thead>
                      <b-tr>
                        <b-th>Depth</b-th>
                        <b-th></b-th>
                        <b-th></b-th>
                        <b-th></b-th>
                      </b-tr>
                    </b-thead>
                    <b-tbody>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '100mm' : '4"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_1 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '900mm' : '36"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_9 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '200mm' : '8"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_2 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1000mm' : '40"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_10 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '300mm' : '12"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_3 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1100mm' : '44"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_11 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '400mm' : '16"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_4 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1200mm' : '48"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_12 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '500mm' : '20"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_5 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1300mm' : '52"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_13 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '600mm' : '24"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_6 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1400mm' : '56"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_14 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '700mm' : '28"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_7 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td><strong>Sensor at {{ uom == 1 ? '1500mm' : '60"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_15 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                      </b-tr>
                      <b-tr>
                        <b-td><strong>Sensor at {{ uom == 1 ? '800mm' : '32"' }}</strong></b-td>
                        <b-td><b-icon variant="success" :icon="model.sensor_placing_8 ? 'check-circle' : 'circle'"
                            aria-hidden="true"></b-icon></b-td>
                        <b-td></b-td>
                        <b-td></b-td>
                      </b-tr>
                    </b-tbody>
                  </b-table-simple>

                  <b-table-simple responsive small fixed v-if="model.device_type == 'Nutrients'">
                    <b-thead>
                      <b-tr>
                        <b-th>Depth</b-th>
                        <b-th>Address</b-th>
                        <b-th>Type</b-th>
                      </b-tr>
                    </b-thead>
                    <b-tbody>
                      <b-tr v-for="(item, idx) in model.sensor_config" :key="idx">
                        <b-td><strong>{{ item.depth }}</strong></b-td>
                        <b-td>{{ item.address.replace('_', '.') }}</b-td>
                        <b-td>{{ item.type }}</b-td>
                      </b-tr>
                    </b-tbody>
                  </b-table-simple>

                </b-col>
              </b-row>

            </b-col>
          </b-row>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="mr-auto" @click="closeCreateSensorTypeModal">Cancel</base-button>
          <base-button type="outline-primary" class="ml-auto" @click="prevStep()"
            v-show="formStep != 'detail'">Previous</base-button>
          <base-button type="primary" @click="formStep == 'confirm' ? addSensorType() : nextStep()"> {{ formStep ==
            'confirm' ? 'Create' : 'Next' }}</base-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">
        <template slot="header">
          <base-button :disabled="!userCan('Add', 'Sensor Types')" @click.native="openCreateSensorTypeModal()" class="btn"
            size="sm" type="primary" v-b-tooltip.hover.top
            title="Add a new sensor type. Sensor types are tied to hardware nodes." icon>
            Add Sensor Type
          </base-button>
          <b-button v-b-modal.modal-4 size="sm" variant="primary" class="btn"
            v-b-tooltip.hover.top title="Read More">
            <b-icon icon="question-circle-fill" aria-label="Help"></b-icon>
          </b-button>
          <b-modal id="modal-4" title="Sensor Types">

              <p>In MAB, each sensor that's connected to a node needs it's own
                configuration. Various nodes of the same type (and entity) can share sensor configurations (like
                templates) as long as they're using the same (or compatible) sensor hardware.</p>
              <p>The Sensor Types screen shows a list of all of the sensor configuration
                templates. A breakdown of each table column is given below:</p>
              
                <div class="table-responsive">
                  <table class="table table-sm">
                  <tbody>
                    <tr>
                      <td><strong>Column</strong></td>
                      <td><strong>Description</strong></td>
                      <td><strong>Additional Notes</strong></td>
                    </tr>
                    <tr>
                      <td>Entity</td>
                      <td>The Node's owning Entity</td>
                      <td>Visible to Admins and Manager roles</td>
                    </tr>
                    <tr>
                      <td>Device Make</td>
                      <td>The Sensor Type Configuration's Title</td>
                      <td><br></td>
                    </tr>
                    <tr>
                      <td>Device Type</td>
                      <td>The Sensor Type</td>
                      <td>The same as the Node's Type</td>
                    </tr>
                    <tr>
                      <td>Actions</td>
                      <td>Various Actionable Buttons</td>
                      <td>Visibility depends on Permissions/Role</td>
                    </tr>
                  </tbody>
                </table>
                </div>

              <h4>Adding a Sensor Type</h4>
              <p>To add a new sensor type configuration to your entity, locate and click
                on the <strong>Add Sensor Type</strong> button at the top left corner of the Sensor Types screen. This
                will open the multi-step <strong>Add New Sensor Type</strong> dialog. Fill in all of the required fields
                for each step (Device Details/Configuration) and click on the <strong>Next</strong> button to proceed to
                each section.</p>
              <p>Once you reach the final confirmation section, please confirm that all
                data was entered correctly and then click on the <strong>Create </strong>button. This will create the
                sensor type configuration record and if successful, a green success notification (top right) would
                display.&nbsp;</p>
              <h4>Removing an Existing Sensor Type</h4>
              <p>To remove a previously added sensor type, locate it in the Sensor Types
                table (either manually or via the Search Input located to the top right of the table). Then, click on the
                Record's <strong>Remove</strong> button (in the actions column). A confirmation dialog will appear. To
                confirm and remove the sensor type, click on <strong>Remove</strong>.<br><br><strong>Please Note:</strong>
                Removing a sensor type record might fail when it's currently being used by one or more nodes.</p>
              <h4>Configure an Existing Sensor Type</h4>
              <p>In order to manage a sensor type configuration, a dedicated sensor type
                configuration screen is used. To access each sensor's configuration screen, locate the
                <strong>Configure</strong> button on the Sensor Types table and click on it.</p>
          </b-modal>
        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadSensorTypes">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="entities && entities.length && entities.length > 1" class="fullwidth" clearable filterable
              placeholder="Filter by Entity.." v-model="filterEntity" @change="loadSensorTypes">
              <el-option v-for="e in entities" :value="e.id" :label="e.company_name" :key="e.id"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText" :debounce="1000" @update="loadSensorTypes" placeholder="Search...">
              </b-input>
            </base-input>
          </b-col>
        </b-row>

        <b-row class='nomargin'>
          <b-col md>
            <b-table striped bordered outlined small stacked="lg" responsive show-empty primary-key="id" no-local-sorting
              @sort-changed="sortingChanged" :fields="tableColumns" :items="tableData" :busy.sync="bInitialQuery">

              <template #cell()="data">
                {{ data.value }}
              </template>

              <template #cell(actions)="data" v-if="canAction">
                <div class="d-flex justify-content-center">
                  <b-button :disabled="!userCan('Clone', 'Sensor Types', data.item.id, 'O')"
                    @click="handleClone(data.index, data.item)" class="btn" variant="outline-primary" size="sm">
                    Clone
                  </b-button>

                  <b-button :disabled="!userCan('Edit', 'Sensor Types', data.item.id, 'O')"
                    @click="handleConfigure(data.index, data.item)" class="btn" variant="outline-primary" size="sm">
                    Configure
                  </b-button>

                  <b-button :disabled="!userCan('Delete', 'Sensor Types', data.item.id, 'O')"
                    @click="handleDelete(data.index, data.item)" class="btn" variant="outline-primary" size="sm">
                    Remove
                  </b-button>
                </div>
              </template>

              <template #table-busy>
                <div class="text-center">
                  <div class='mab_spinner'></div>
                </div>
              </template>

              <template #emptyfiltered>
                <div class="text-center">
                  No matches found
                </div>
              </template>

              <template #empty>
                <div class="text-center">
                  No entries found
                </div>
              </template>

            </b-table>
          </b-col>
        </b-row>

        <div slot="footer" class='align-right'>
          <b-row>
            <b-col md>
              Showing {{ Math.min(1 + (perPage * (currentPage - 1)), totalRows) }} to {{ Math.min((perPage * (currentPage - 1))
                + perPage, totalRows) }} of {{ totalRows }} entries
            </b-col>
            <b-col md>
              <b-pagination @input="loadSensorTypes" v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                align="right"></b-pagination>
            </b-col>
          </b-row>
        </div>

      </card>

    </b-container>
  </div>
</template>
<script>

import { parse } from 'date-fns';
import { Select, Option, OptionGroup, Steps, Step } from 'element-ui';
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [mab_utils],

  components: {
    [Steps.name]: Steps,
    [Step.name]: Step,
    [Select.name]: Select,
    [Option.name]: Option,
    [OptionGroup.name]: OptionGroup
  },

  data() {
    return {

      loading: false,
      bInitialQuery: true,

      tableColumns: [
        {
          key: 'device_make',
          label: 'Device Make',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'device_type',
          label: 'Device Type',
          sortable: true,
          tdClass: 'valign'
        }
      ],

      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,

      formStep: 'detail',
      formSteps: ['detail', 'config', 'confirm'],
      formStepIndex: 0,
      formsValid: false,

      filterText: '',
      filterEntity: '',
      entities: [],
      sortBy: 'id',
      sortDir: 'desc',

      showNewSensorTypeModal: false,
      showCloneSensorModal: false,

      uom: this.$store.getters.getUOM,

      model: {
        device_type: "",
        device_make: "",
        device_category: "",
        company_id: this.$store.state.user_obj.company_id,

        device_length: "",
        sensor_placing_1: "",
        sensor_placing_2: "",
        sensor_placing_3: "",
        sensor_placing_4: "",
        sensor_placing_5: "",
        sensor_placing_6: "",
        sensor_placing_7: "",
        sensor_placing_8: "",
        sensor_placing_9: "",
        sensor_placing_10: "",
        sensor_placing_11: "",
        sensor_placing_12: "",
        sensor_placing_13: "",
        sensor_placing_14: "",
        sensor_placing_15: "",
        sensor_config: [],

        diameter: 1,
        pulse_weight: 1,
        measurement_type: "",
        application_type: "",

        group_id: null
      },

      clone_model: {
        sensor_id: null,
        company_id: null,
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
        'Soil Moisture', 'Temperature', 'E.C', 'Nutrient 1', 'Nutrient 2', 'Nutrient 3', 'Nutrient 4'
      ],

      companies: [],
      groups: [],

      multipleCompanies: false,
      canAction: false

    };
  },
  methods: {

    loadSensorTypes() {

      this.loading = true;

      this.$axios.post("/api/hardwaremanagementtable", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial: this.bInitialQuery,
        filter: this.filterText,
        entity: this.filterEntity,
        sort_by: this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      })
        .then(resp => {

          this.loading = false;
          this.bInitialQuery = false;
          this.entities = resp.data.entities;

          if (this.isAdmin() || this.userLimits('View', 'Sensor Types', 'C').length > 1) {
            this.multipleCompanies = true;
            this.tableColumns.unshift({
              key: 'company_name',
              label: 'Entity',
              sortable: true,
              tdClass: 'valign'
            });
          }

          if (this.isAdmin() || this.userCan('Edit', 'Sensor Types') || this.userCan('Delete', 'Sensor Types')) {
            this.canAction = true;
            this.tableColumns.push({
              key: 'actions',
              label: 'Actions',
              thClass: 'halign'
            });
          }

          this.tableData = resp.data.rows;
          this.totalRows = resp.data.total;

          if (this.totalRows == 0) { this.currentPage = 1; }

        });
    },

    loadCompanies() {
      this.$axios.post("/api/companies_list", {
        context: [
          { verb: 'Add', module: 'Sensor Types' },
          { verb: 'Clone', module: 'Sensor Types' },
        ]
      }).then((resp) => {
        this.companies = resp.data.companies;
      });
    },

    loadGroups() {
      this.$axios.post("/api/groups_list", {
        company_id: this.model.company_id ? this.model.company_id : this.$store.state.user_obj.company_id,
        context: [{ verb: 'Add', module: 'Sensor Types' }]
      })
        .then((resp) => {
          this.groups = resp.data.groups;
        });
    },

    openCreateSensorTypeModal() {
      this.showNewSensorTypeModal = true;
    },

    closeCreateSensorTypeModal() {
      this.clearModel();
      this.showNewSensorTypeModal = false;
    },

    openCloneSensorModal(row) {
      this.clone_model.sensor_id = row.id;
      this.showCloneSensorModal = true;
    },

    closeCloneSensorModal() {
      this.clearCloneModel();
      this.showCloneSensorModal = false;
    },

    clearCloneModel() {
      this.clone_model.sensor_id = null;
      this.clone_model.company_id = null;
    },

    clearModel() {
      this.formStep = 'detail';
      this.formStepIndex = 0;

      this.model.device_type = "";
      this.model.device_make = "";
      this.model.device_category = "";
      this.model.company_id = this.$store.state.user_obj.company_id;
      this.model.group_id = null;

      this.model.device_length = "";
      this.model.sensor_placing_1 = "";
      this.model.sensor_placing_2 = "";
      this.model.sensor_placing_3 = "";
      this.model.sensor_placing_4 = "";
      this.model.sensor_placing_5 = "";
      this.model.sensor_placing_6 = "";
      this.model.sensor_placing_7 = "";
      this.model.sensor_placing_8 = "";
      this.model.sensor_placing_9 = "";
      this.model.sensor_placing_10 = "";
      this.model.sensor_placing_11 = "";
      this.model.sensor_placing_12 = "";
      this.model.sensor_placing_13 = "";
      this.model.sensor_placing_14 = "";
      this.model.sensor_placing_15 = "";
      this.model.sensor_config = [];

      this.model.diameter = "";
      this.model.pulse_weight = "";
      this.model.measurement_type = "";
      this.model.application_type = "";

    },

    addSensorType() {
      console.log(this.model);
      if (this.formsValid) {
        this.loading = true;
        this.$axios.post("/api/hardwaremanagementnew", this.model)
          .then((response) => {
            this.loading = false;
            if (response.data.message == 'sensor_added') {
              this.loadSensorTypes();
              this.closeCreateSensorTypeModal();
              this.$notify({
                title: 'Success',
                message: 'New sensor type added',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
              this.formsValid = false;
            }
          }).catch(err => {
            this.loading = false;
            if (err.response.data.errors) {
              this.$refs.form.setErrors(err.response.data.errors);
            }
          });
      }
    },

    /* Clone Sensor */

    cloneSensor() {
      console.log(this.model);
      this.$refs.clone_sensor_form.validate()
        .then(result => {
          if (!result) { return; }

          this.loading = true;
          this.$axios.post("/api/hardwaremanagementclone", this.clone_model)
            .then((response) => {
              this.loading = false;
              if (response.data.message == 'sensor_cloned') {
                this.loadSensorTypes();
                this.closeCloneSensorModal();
                this.$notify({
                  title: 'Success',
                  message: 'Sensor cloned',
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
            });
        });
    },

    /* Wizard Methods */

    nextStep() {
      this.$refs["addstep_" + this.formSteps[this.formStepIndex]].validate()
        .then(result => {
          if (!result) { this.formsValid = false; return; }
          this.formsValid = true;
          if (this.formStepIndex < (this.formSteps.length - 1)) {
            this.formStepIndex++;
            this.formStep = this.formSteps[this.formStepIndex];
            console.log(this.model);
          }
        });
    },

    prevStep() {
      if (this.formStepIndex > 0) {
        this.formStepIndex--;
        this.formStep = this.formSteps[this.formStepIndex];
      }
      this.$refs["addstep_" + this.formSteps[this.formStepIndex]].reset();
    },

    /* Dialog Sensor Row Repeater Methods */

    addSensorConfigRow() {
      if (this.sensor_model.address && this.sensor_model.depth && this.sensor_model.type) {
        this.model.sensor_config.push(JSON.parse(JSON.stringify(this.sensor_model)));

        // always stored as metric, so safe calc to do:
        let num = parseInt(parseInt(this.sensor_model.depth.replace(/mm/g, '')) / 100);
        this.model['sensor_placing_' + num] = true;
        // clear model

        this.sensor_model.address = '';
        this.sensor_model.depth = '';
        this.sensor_model.type = '';

        this.$refs["addstep_config"].reset();

        console.log(this.model.sensor_config);
      }
    },

    removeSensorConfigRow(index) {
      this.model.sensor_config.splice(index, 1);
    },

    /* Table Methods */

    handleConfigure(index, row) {
      console.log(row);
      this.$router.push("/sensor_types/edit/" + row.id)
    },

    handleClone(index, row) {
      this.openCloneSensorModal(row);
    },

    handleDelete(index, row) {
      swal.fire({
        title: 'Are you sure?',
        text: `Please confirm sensor type removal`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.deleteSensor(row);
        }
      });
    },

    deleteSensor(row) {
      this.loading = true;
      this.$axios.post("/api/hardwaremanagementdestroy", { id: row.id })
        .then((response) => {
          this.loading = false;
          if (response.data.message == 'sensor_removed') {

            let indexToDelete = this.tableData.findIndex(
              tableRow => tableRow.id === row.id
            );
            if (indexToDelete >= 0) {
              this.tableData.splice(indexToDelete, 1);
            }

            this.$notify({
              title: 'Success',
              message: 'Sensor type has been removed',
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
        });
    },

    sortingChanged(e) {
      this.sortBy = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadSensorTypes();
    }
  },

  // filter change reset page
  watch: { filterText: function (n, o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted() {
    this.loadSensorTypes();
    this.loadCompanies();
    this.loadGroups();
  }
};
</script>
<style>
.no-border-card .card-footer {
  border-top: 0;
}

.modal-title {
    font-size: 2.0625rem;
}
</style>
