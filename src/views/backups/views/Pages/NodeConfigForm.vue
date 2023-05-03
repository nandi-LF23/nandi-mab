<template>
  <div class="content">
    <base-header class="pb-6" type="nodeconfig">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Node Configuration - {{ $route.params.node_address }}</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>

      <!-- Upload Perimeter Modal -->
      <b-modal v-model="showUploadPerimeterModal" centered>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Upload Zone File</h6>
        </template>
        <template #default="{ hide }">
          <validation-observer ref="uploadform" slim>
            <form role="form" autocomplete="off" @submit.prevent="() => false">
              <b-row>
                <b-col md>
                  <base-input label="Import Type" name="import type" rules="required" vid="type">
                    <el-select v-model="upload_model.type" filterable placeholder="Import Type">
                      <el-option label="Geographic File" value='gis' selected></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Data Format" name="data format" rules="required" vid="format">
                    <el-select v-model="upload_model.format" filterable placeholder="Data Format">
                      <el-option label="Zone File (.zip)" value='zip' selected></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <validation-provider v-slot="{ errors, validate }">
                    <base-input label="Zone File (.zip)" name="file" rules="required" vid="filedata">
                      <b-form-file v-model="upload_model.filedata" @change="validate"
                        placeholder="Select or drop file here.." drop-placeholder="Drop file here...">
                      </b-form-file>
                    </base-input>
                  </validation-provider>
                </b-col>
              </b-row>

              <b-row v-if="upload_model.log">
                <b-col md>
                  <b-form-textarea class="importlog" ref="importlog" v-model="upload_model.log"
                    placeholder="Log output.." readonly plaintext rows="10" max-rows="10">
                  </b-form-textarea>
                </b-col>
              </b-row>

              <b-row v-if="upload_model.progress">
                <b-col md>
                  <b-progress :max="100" height="2rem">
                    <b-progress-bar :value="upload_model.progress" :label="upload_model.progress + '%'"
                      :variant="upload_model.variant" :animated="!upload_model.complete"></b-progress-bar>
                  </b-progress>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>
        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="ml-auto"
            @click="upload_model.busy ? cancelUpload() : closeUploadPerimeterModal()" :disabled="upload_model.complete">
            Cancel
          </base-button>
          <base-button type="primary"
            @click="upload_model.complete ? closeUploadPerimeterModal() : uploadPerimeterFiles()"
            :disabled="upload_model.busy && !upload_model.complete">
            {{ (upload_model.busy && !upload_model.complete) ? "Busy" : (upload_model.complete ? "Done" : "Upload") }}
          </base-button>


        </template>
      </b-modal>

      <!-- Set Perimeter Modal -->
      <b-modal v-model="showSetPerimeterModal" centered>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Set Perimeter &amp; Zones</h6>
        </template>
        <template #default="{ hide }">
          <b-row>
            <b-col>
              <base-input label=" ">
                <MapPolyDrawer :fullWidthBtn="true" :accessToken="$store.state.mapBoxAccessToken"
                  modalTitle="Draw Perimeter" buttonTitle="Draw Perimeter" :startPos="startPos"
                  :polyData="model.perimeter" :additionalLayers="additional_layers" @savePolyData="savePerimeter"
                  @cancelPolyDraw="closeSetPerimeterModal">
                </MapPolyDrawer>
              </base-input>
            </b-col>
            <b-col>
              <base-input label=" ">
                <b-button variant="outline-primary" :block="true" @click="openUploadPerimeterModal">Upload Zone File
                </b-button>
              </base-input>
            </b-col>
          </b-row>
          <b-row>
            <b-col>
              <base-input label=" ">
                <b-button variant="outline-primary" :block="true" @click="confirmPerimeterClearance">Clear Perimeter
                </b-button>
              </base-input>
            </b-col>
            <b-col>
              <base-input label=" ">
                <b-button variant="outline-primary" :block="true" @click="confirmFieldZonesClearance">Clear Zones
                </b-button>
              </base-input>
            </b-col>
          </b-row>
        </template>
        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeSetPerimeterModal">Cancel</b-button>
        </template>
      </b-modal>

      <!-- Change Node Entity -->
      <b-modal v-model="showNodeEntityChangeModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Change Node Entity</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='entity_change_form' slim>
            <form role="form" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <b-toast :visible="true" variant="warning" title="Important" static no-auto-hide>
                    Changing a node's entity would cause:
                    <ul>
                      <li>Removal of the node from any security groups</li>
                      <li>Removal of the node from any integrations</li>
                      <li v-if="model.node_type == 'Nutrients'">The Node's Nutrient Template to revert to Default</li>
                    </ul>
                  </b-toast>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Entity" name="company" rules="required" vid="company" placeholder="Entity"
                      autocomplete="off">
                      <el-select :disabled="!model.node_type || Object.values(companies).length < 2"
                        @change="companyChanged" v-model="model.company_id" filterable placeholder="Entity"
                        v-b-tooltip.hover.top
                        title="Change the node's owning entity. Note: After changing the entity, you have to also choose a new device before the changes would take effect."
                        autocomplete="off">
                        <template v-if="model.node_type && Object.values(companies).length">
                          <template v-for="company in companies">
                            <el-option v-if="
                              userCan('Edit', 'Node Config', company.id, 'C') ||
                              userCan('Edit', convertNodeTypeToSubsystem(model.node_type), company.id, 'C')
                            " :key="company.id" :label="company.company_name" :value="company.id">
                              <span>{{ company.company_name }}</span>
                            </el-option>
                          </template>
                        </template>
                      </el-select>
                    </base-input>
                  </div>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Device Type" name="device type" rules="required" vid="hardware_management_id">
                      <el-select v-model="model.hardware_management_id">
                        <el-option v-for="device in device_types" :key="device.id" :label="device.device_make"
                          :value="device.id">
                          <span style="float: left">{{ device.device_make }}</span>
                          <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{
                              device.company_name
                          }}</span>
                        </el-option>
                      </el-select>
                    </base-input>
                  </div>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeNodeEntityChangeModal">Cancel</b-button>
          <b-button variant="primary" @click='changeNodeEntity'>Change Node Entity</b-button>
        </template>
      </b-modal>

      <!-- Change Node Type Modal -->
      <b-modal v-model="showNodeTypeChangeModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Change Node Type</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='change_node_type_form' slim>
            <form role="form" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <b-toast :visible="true" variant="warning" title="Important" static no-auto-hide>
                    Changing a node's type would cause:
                    <ul>
                      <li>Removal of the node from any security groups</li>
                      <li>Removal of the node from any integrations</li>
                    </ul>
                  </b-toast>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Node Type" name="node type" rules="required" vid="node_type">
                      <el-select @change="nodeTypeChanged" v-model="model.node_type" filterable placeholder="Node Type">
                        <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Soil Moisture')"
                          label="Soil Moisture" value="Soil Moisture"></el-option>
                        <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Nutrients')"
                          label="Nutrients" value="Nutrients"></el-option>
                        <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Well Controls')"
                          label="Well Controls" value="Wells"></el-option>
                        <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Meters')"
                          label="Water Meter" value="Water Meter"></el-option>
                      </el-select>
                    </base-input>
                  </div>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Device Type" name="device type" rules="required" vid="hardware_management_id">
                      <el-select v-model="model.hardware_management_id">
                        <el-option v-for="device in device_types" :key="device.id" :label="device.device_make"
                          :value="device.id">
                          <span style="float: left">{{ device.device_make }}</span>
                          <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{
                              device.company_name
                          }}</span>
                        </el-option>
                      </el-select>
                    </base-input>
                  </div>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeNodeTypeChangeModal">Cancel</b-button>
          <b-button variant="primary" @click='changeNodeType'>Change Node Type</b-button>
        </template>
      </b-modal>

      <!-- Change Node Address Modal -->
      <b-modal v-model="showAddressChangeModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Change Node Address</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='change_address_form' slim>
            <form role="form" @submit.prevent="() => false">
              <b-row>
                <b-col>
                  <div>
                    <base-input label="Node Address" vid="node_address" rules="required" name="node address"
                      placeholder="Node Address" v-model="node_address_edit"></base-input>
                  </div>
                </b-col>

                <b-col>
                  <div>
                    <base-input label="Probe Address" vid="probe_address" name="probe_address"
                      placeholder="Probe Address" v-model="probe_address_edit"></base-input>
                  </div>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeAddressChangeModal">Cancel</b-button>
          <b-button variant="primary" @click='changeNodeAddress'>Change Address</b-button>
        </template>
      </b-modal>

      <validation-observer ref='form' slim>
        <div class='row'>
          <div class='col-md-12'>
            <card body-classes="px-0 py-0">
              <template slot="header">

                <base-button v-if="model.node_type == 'Soil Moisture' || model.node_type == 'Nutrients'"
                  :disabled="!userCan('Edit', 'Node Config') || loading" @click.native="openSetPerimeterModal"
                  class="btn" size="sm" type="primary" icon>
                  Set Perimeter &amp; Zones
                </base-button>

              </template>
              <div class="card-body">
                <b-row align-v="center">
                  <b-col md>
                    <div>
                      <base-input label="Node Address">
                        <b-input-group>
                          <b-form-input type="text" v-model="model.node_address" placeholder="Node Address" readonly>
                          </b-form-input>
                          <b-input-group-append>
                            <b-button :disabled="!isDistributor() && !isAdmin()" variant="outline-primary"
                              @click="openAddressChangeModal">Change</b-button>
                          </b-input-group-append>
                        </b-input-group>
                      </base-input>
                    </div>
                  </b-col>

                  <b-col md>
                    <div>
                      <base-input label="Node Type">
                        <b-input-group>
                          <b-form-input type="text" v-model="model.node_type" placeholder="Node Type" readonly>
                          </b-form-input>
                          <b-input-group-append>
                            <b-button :disabled="!isDistributor() && !isAdmin()" variant="outline-primary"
                              @click="openNodeTypeChangeModal">Change</b-button>
                          </b-input-group-append>
                        </b-input-group>
                      </base-input>
                    </div>
                  </b-col>

                  <b-col md>
                    <div>
                      <base-input label="Commissioning Date" v-model="model.commissioning_date" disabled></base-input>
                    </div>
                  </b-col>
                </b-row>

                <b-row align-v="center">
                  <b-col md>
                    <div>
                      <base-input @change="syncFields" name="latitude" :rules="{ regex: /^-?\d+(\.\d{1,15})?$/ }"
                        label="Latitude" placeholder="Latitude" vid="latt" v-model="model.latt"></base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input @change="syncFields" name="longitude" :rules="{ regex: /^-?\d+(\.\d{1,15})?$/ }"
                        label="Longitude" placeholder="Longitude" vid="lng" v-model="model.lng"></base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input label=" ">
                        <MapCoordsPicker :fullWidthBtn="true" :accessToken="$store.state.mapBoxAccessToken"
                          modalTitle="Pinpoint Coordinates" buttonTitle="Set Coords Visually"
                          :additionalLayers="additional_layers" :startPos="startPos" @setCoords="setCoords">
                        </MapCoordsPicker>
                      </base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input label=" " v-b-tooltip.hover.top
                        title="Locking the coordinates will prevent them from being overridden by device updates.">
                        <b-form-checkbox size="lg" @change="syncFields" v-model="model.coords_locked"
                          :unchecked-value="0" :value="1">
                          Lock Coordinates?
                        </b-form-checkbox>
                      </base-input>
                    </div>
                  </b-col>
                </b-row>

                <b-row align-v="center">
                  <b-col md>
                    <div>
                      <base-input @change="syncFields" name="" label="Node Serial Number" placeholder="Node Serial"
                        vid="wl_station_name" v-b-tooltip.hover.top title="The node's serial number"
                        v-model="model.node_serial_number">
                      </base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input @change="syncFields" name="station name" label="W.Station Name"
                        placeholder="W.Station Name" vid="wl_station_name" v-b-tooltip.hover.top
                        title="Optionally enter an associated weather station's name." v-model="model.wl_station_name">
                      </base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input label="Entity">
                        <b-input-group>
                          <b-form-input type="text" v-model="node_company_name" placeholder="Entity" readonly>
                          </b-form-input>
                          <b-input-group-append>
                            <b-button :disabled="!isDistributor() && !isAdmin()" variant="outline-primary"
                              @click="openNodeEntityChangeModal">Change</b-button>
                          </b-input-group-append>
                        </b-input-group>
                      </base-input>
                    </div>
                  </b-col>
                </b-row>
                <b-row>
                  <b-col cols="6" md="8">
                    <div>
                      <base-input @change="syncFields" name="field name" rules="required" label="Field Name"
                        placeholder="Field Name" vid="field_name" v-model="model.field_name">
                      </base-input>
                    </div>
                  </b-col>
                  <b-col cols="6" sm="4">
                    <div>
                      <base-input @change="syncFields" name="field zone id" rules="integer" label="Field Zone ID"
                        placeholder="Field Zone ID" vid="zone" v-model="model.zone">
                      </base-input>
                    </div>
                  </b-col>
                </b-row>
              </div>
            </card>
          </div>

          <div class='col-md-12'>
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">Node Config - Advanced - {{ model.node_type }}</h3>
              </template>

              <div class="card-body">
                <b-row align-v="center">
                  <b-col md>
                    <div>
                      <base-input label="Sensor Type" name="device type" rules="required" vid="hardware_management_id">
                        <el-select @change="syncFields" v-model="model.hardware_management_id">
                          <el-option v-for="device in device_types" :key="device.id" :label="device.device_make"
                            :value="device.id">
                            <span style="float: left">{{ device.device_make }}</span>
                            <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{
                                device.company_name
                            }}</span>
                          </el-option>
                        </el-select>
                      </base-input>
                    </div>
                  </b-col>
                  <b-col md>
                    <div>
                      <base-input @change="syncFields" name="device serial number" rules="required"
                        label="Device Serial Number" placeholder="Device Serial" vid="device_serial_number"
                        v-model="model.device_serial_number"></base-input>
                    </div>
                  </b-col>
                </b-row>
              </div>
            </card>
          </div>

          <div class='col-md-12'
            v-if="model.integration_opts && Object.keys(model.integration_opts).length && userCan('Integrate', 'Entities')">
            <card body-classes="px-0 py-0">
              <template slot="header">
                <h3 class="mb-0">Node Config - Integration Options</h3>
              </template>

              <div class="card-body">
                <template v-for="(opts, slug) in model.integration_opts">
                  <b-row :key="slug">
                    <b-col>
                      <h4>{{ slug }} Options</h4>
                    </b-col>
                  </b-row>
                  <b-row>
                    <b-col v-for="(opt, opt_key) in opts.hardware_config" :key="opt_key" sm="4">
                      <base-input v-if="opt.type == 'bool'" :label="opt.label">
                        <b-form-checkbox @change="syncFields" v-model="opt.value" value="1" unchecked-value="0">
                          {{ opt.desc ? opt.desc : opt.label }}
                        </b-form-checkbox>
                      </base-input>
                    </b-col>
                  </b-row>
                </template>
              </div>
            </card>
          </div>

        </div>
      </validation-observer>
    </div>
  </div>
</template>
<script>
import { Select, Option } from 'element-ui'
import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import swal from 'sweetalert2';
/* eslint-disable no-new */
import mab_utils from '../../util/mab-utils';
import MapCoordsPicker from '../../custom_components/MapCoordsPicker';
import MapPolyDrawer from '../../custom_components/MapPolyDrawer';

export default {

  mixins: [mab_utils],

  components: {
    MapCoordsPicker,
    MapPolyDrawer,
    flatPicker,
    [Select.name]: Select,
    [Option.name]: Option
  },
  data() {
    return {

      loading: false,

      model: {
        id: '', // node id
        node_address: '',
        probe_address: '',
        node_type: '',
        commissioning_date: '',
        field_name: '',
        latt: '',
        lng: '',
        zone: '',
        coords_locked: 0,
        node_make: null,
        hardware_management_id: '',
        node_serial_number: null,
        device_serial_number: null,
        wl_station_name: '',
        company_id: null,
        perimeter: null,
        integration_opts: null
      },

      additional_layers: null,

      upload_model: {
        type: 'gis',
        format: 'zip',
        filedata: '',
        log: '',
        progress: 0,
        busy: false,
        complete: false,
        variant: 'success'
      },

      field_id: null,

      companies: [],
      device_types: [],

      node_company_name: '',

      showUploadPerimeterModal: false,
      showSetPerimeterModal: false,
      showAddressChangeModal: false,
      showNodeTypeChangeModal: false,
      showNodeEntityChangeModal: false,

      // for when cancelling the changes
      prev_company_id: null,
      prev_hardware_management_id: null,
      prev_node_type: null,

      node_address_edit: '',
      probe_address_edit: '',
      startPos: { lat: 0, lng: 0 }
    };
  },

  methods: {

    loadHardware() {
      this.loading = true;
      this.$axios.get("/api/hardwareconfig/" + this.$route.params.node_address)
        .then((resp) => {
          this.loading = false;
          if (resp.data.message == 'hardware_loaded') {
            this.model = resp.data.hardware;

            this.field_id = resp.data.field_id;

            if (this.model.perimeter) {
              this.model.perimeter = JSON.parse(this.model.perimeter);
            }

            if (resp.data.additional_layers) {
              this.additional_layers = JSON.parse(resp.data.additional_layers);
            }

            this.startPos = { lat: this.model.latt, lng: this.model.lng };

            var node_address = this.model.node_address.substring(0, this.model.node_address.lastIndexOf("-"));
            var probe_address = this.model.node_address.substring(this.model.node_address.lastIndexOf("-") + 1, this.model.node_address.length);

            if (node_address) {
              this.node_address_edit = node_address;
            }
            if (probe_address) {
              this.probe_address_edit = probe_address;
            }
            this.loadCompanies();
            this.loadSensorTypes();
          }

        });
    },

    syncFields() {
      return this.$refs.form.validate()
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
            return false;
          }

          this.loading = true;
          return this.$axios.post("/api/hardwareconfigsave", this.model)
            .then((response) => {
              this.loading = false;
              if (response.data.message == 'success') {

                this.$notify({
                  title: 'Saved',
                  message: 'Changes were saved',
                  type: 'success',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });
              }
              return response;
            }).catch(err => {
              this.loading = false;
              if (err.response.data.errors) {
                this.$refs.form.setErrors(err.response.data.errors);
              }
              return err;
            });
        });
    },

    loadCompanies() {
      let subsystem = this.convertNodeTypeToSubsystem(this.model.node_type);
      if (subsystem) {
        if (this.userCan('Edit', 'Node Config')) {
          this.$axios.post("/api/companies_list", {
            context: [
              { verb: 'Edit', module: 'Node Config' },
              { verb: 'Edit', module: subsystem }
            ]
          }).then((resp) => {
            this.companies = resp.data.companies;
            this.node_company_name = this.companies['"' + this.model.company_id.toString() + '"'].company_name;
          });

        } else if (this.userCan('Edit', subsystem)) {
          this.$axios.post("/api/companies_list", {
            context: [
              { verb: 'Edit', module: subsystem }
            ]
          }).then((resp) => {
            this.companies = resp.data.companies;
            this.node_company_name = this.companies['"' + this.model.company_id.toString() + '"'].company_name;
          });
        }
      }
    },

    loadSensorTypes() {
      this.$axios.post("/api/hardwaremanagementlist", {
        device_type: this.model.node_type,
        company_id: this.model.company_id
      })
        .then((resp) => {
          this.device_types = resp.data.hardware;
        });
    },

    companyChanged() {
      this.prepSensorChange();
    },

    nodeTypeChanged() {
      this.prepSensorChange();
    },

    prepSensorChange() {
      this.model.hardware_management_id = '';
      this.loadSensorTypes();
    },

    openUploadPerimeterModal() { this.showUploadPerimeterModal = true; },

    closeUploadPerimeterModal() {
      this.resetUploadModel();
      this.closeSetPerimeterModal();
      this.showUploadPerimeterModal = false;
    },

    cancelUpload(log_it = true) {
      this.upload_model.busy = false;
      this.upload_model.complete = false;
      this.upload_model.progress = 0;
      if (log_it) {
        this.importLog("Canceling Upload");
      }
    },

    fileSelectionChange(validate_func) {
      validate_func();
      this.upload_model.busy = false;
      this.upload_model.complete = false;
      this.upload_model.progress = 0;
      this.importLog("New file selected", true);
    },

    importLog(message, replace = false) {
      if (replace) {
        this.upload_model.log = message + "\n";
      } else {
        this.upload_model.log += message + "\n";
      }
      this.$nextTick(() => {
        if (this.$refs.importlog) {
          this.$refs.importlog.$el.scrollTop = this.$refs.importlog.$el.scrollHeight;
        }
      });
    },

    openSetPerimeterModal() { this.showSetPerimeterModal = true; },
    closeSetPerimeterModal() { this.showSetPerimeterModal = false; },

    openAddressChangeModal() { this.showAddressChangeModal = true; },
    closeAddressChangeModal() { this.showAddressChangeModal = false; },

    openNodeTypeChangeModal() {
      this.showNodeTypeChangeModal = true;
      this.prev_node_type = this.model.node_type;
      this.prev_hardware_management_id = this.model.hardware_management_id;
    },

    openNodeEntityChangeModal() {
      this.showNodeEntityChangeModal = true;
      this.prev_company_id = this.model.company_id;
      this.prev_hardware_management_id = this.model.hardware_management_id;
    },

    closeNodeTypeChangeModal() {
      this.showNodeTypeChangeModal = false;
      this.model.node_type = this.prev_node_type;
      this.model.hardware_management_id = this.prev_hardware_management_id;
      this.loadSensorTypes();
    },

    closeNodeEntityChangeModal() {
      this.showNodeEntityChangeModal = false;
      this.model.company_id = this.prev_company_id;
      this.model.hardware_management_id = this.prev_hardware_management_id;
      this.loadSensorTypes();
    },

    resetUploadModel() {
      this.upload_model = {
        filedata: '',
        log: '',
        progress: 0,
        busy: false,
        complete: false,
        variant: 'success'
      };
    },

    uploadPerimeterFiles() {
      this.$refs.uploadform.validate().then(success => {

        if (!success) { return; }

        this.loading = true;
        this.upload_model.busy = true;
        this.importLog("STARTING UPLOAD");
        this.importLog("Uploading file..");
        this.upload_model.progress = 10;

        let fd = new FormData();
        fd.set('type', this.upload_model.type);
        fd.set('format', this.upload_model.format);
        fd.append('filedata', this.upload_model.filedata);

        this.$axios.post("/api/dataImport", fd, { headers: { 'Content-Type': 'multipart/form-data' } })
          .then(async (resp) => {

            this.loading = false;
            let data = resp.data;

            if (data.status == 'file_uploaded') {
              this.importLog("File uploaded");
            } else if (data.status == 'unsupported_file') {
              this.importLog("Unsupported File Uploaded");
              this.cancelUpload(false);
              return;
            } else if (data.status == 'upload_error') {
              this.importLog("File upload error, aborting..");
              this.cancelUpload(false);
              return;
            } else if (data.status == 'file_extraction_error' || data.status == 'invalid_or_missing_file') {
              this.importLog("Invalid/corrupt Zip file, aborting..");
              this.cancelUpload(false);
              return;
            } else if (data.status == 'file_multiple') {
              this.importLog("More than one " + data.file + " present, aborting..");
              this.cancelUpload(false);
              return;
            } else if (data.status == 'file_missing') {
              this.importLog(data.file + " file missing from archive, aborting..");
              this.cancelUpload(false);
              return;
            } else {
              this.importLog("Server-side error occurred, aborting..");
              this.cancelUpload(false);
              return;
            }

            this.upload_model.progress = 40;
            if (data.type && data.type == 'gis') {
              if (data.format && (data.format == 'zip')) {

                let processing = true;

                while (processing) {
                  this.loading = true;

                  let lresp = await this.$axios.post(
                    "/api/dataImport",
                    {
                      ticket: data.ticket,
                      type: data.type,
                      format: data.format,
                      field_id: this.field_id
                    }
                  );

                  this.loading = false;
                  let ldata = lresp.data;

                  // ERRORS
                  if (ldata.status == 'general_error') {
                    this.importLog("Server-side error occurred, aborting..");
                    if (ldata.output) {
                      this.importLog(ldata.output);
                    }
                    this.upload_model.progress = 0;
                    this.upload_model.busy = false;
                    processing = false;
                  }

                  // FINISHED
                  if (ldata.status == 'processing_complete') {
                    this.upload_model.progress = 100;
                    this.importLog("PROCESSING COMPLETED");
                    if (ldata.output) {
                      this.importLog(ldata.output);
                    }
                    processing = false;
                    this.upload_model.busy = false;
                    this.upload_model.complete = true;
                  }
                }
              }
            }
            this.loading = false;
          });

      }).catch(err => {
        this.upload_model.progress = 0;
        this.upload_model.busy = false;
        this.importLog("Server-side error occurred.");
      });
    },

    setCoords(coords) {
      this.model.latt = coords.lat;
      this.model.lng = coords.lng;
      this.syncFields();
    },

    savePerimeter(data) {
      this.closeSetPerimeterModal();
      if (data && typeof data.type !== 'undefined' && data.type == 'FeatureCollection') {
        this.model.perimeter = data; // Ensure we only emit a FeatureCollection
        this.syncFields();
      }
    },

    changeNodeAddress() {
      this.$refs.change_address_form.validate().then(success => {

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
        this.$axios.post("/api/hardwareconfigaddrchange",
          {
            old_address: this.model.node_address,
            node_address: this.node_address_edit,
            probe_address: this.probe_address_edit
          }
        ).then((response) => {
          this.loading = false;
          if (response.data.message == 'success') {
            this.model.node_address = this.node_address_edit + '-' + this.probe_address_edit;
            this.model.probe_address = this.probe_address_edit;
            this.closeAddressChangeModal();
            this.$notify({
              title: 'Success',
              message: 'Node address changed. ' + response.data.count + ' row(s) updated.',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if (err.response.data.errors) {
            this.$refs.change_address_form.setErrors(err.response.data.errors);
          }
        });
      });
    },

    changeNodeType() {
      this.$refs.change_node_type_form.validate().then(success => {

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

        this.syncFields().then(() => {
          this.$notify({
            title: 'Success',
            message: 'Node type changed.',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
          this.showNodeTypeChangeModal = false;
        });
      });
    },

    changeNodeEntity() {
      this.$refs.entity_change_form.validate()
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
            return false;
          }

          this.syncFields()
            .then(() => {
              this.$notify({
                title: 'Success',
                message: 'Node entity changed.',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
              this.showNodeEntityChangeModal = false;
            }).catch(err => {
              this.$notify({
                title: 'Failure',
                message: 'Node entity change failed.',
                type: 'warning',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
              this.showNodeEntityChangeModal = false;
            });
        });
    },

    confirmPerimeterClearance() {
      swal.fire({
        title: 'Clear Perimeter',
        text: `Please confirm field perimeter clearance`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Clear',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.clearFieldPerimeter(this.field_id);
        }
      });
    },

    clearFieldPerimeter(field_id) {

      if (!field_id) { console.log("Error! missing field_id"); return; }

      this.loading = true;
      this.$axios.post("/api/clearfieldperimeter", {
        field_id: field_id
      }).then((response) => {
        this.loading = false;
        if (response.data.message == 'success') {
          this.$notify({
            title: 'Success',
            message: 'Field Perimeter cleared.',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      }).catch(err => {
        this.loading = false;
        if (err.response.data.errors) {
          console.log(err.response.data.errors);
        }
      });
    },

    confirmFieldZonesClearance() {
      swal.fire({
        title: 'Clear Zones',
        text: `Please confirm field zones clearance`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Clear',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          // call Zone Clear Endpoint
          this.clearFieldZones(this.field_id);
        }
      });
    },

    clearFieldZones(field_id) {
      if (!field_id) { console.log("Error! missing field_id"); return; }

      this.loading = true;
      this.$axios.post("/api/clearfieldzones", {
        field_id: field_id
      }).then((response) => {
        this.loading = false;
        if (response.data.message == 'success') {
          this.$notify({
            title: 'Success',
            message: 'Field Zones cleared.',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      }).catch(err => {
        this.loading = false;
        if (err.response.data.errors) {
          console.log(err.response.data.errors);
        }
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
