<template>
  <div class="content">
    <base-header class="pb-6" type="nodeconfig">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Node Configuration</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add New Node -->
      <b-modal v-model="showNewNodeModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Add New Node</h6>
        </template>

        <template #default="{ hide }">

          <el-steps :active="formStepIndex" finish-status="success" align-center>
            <el-step title="Node Details"></el-step>
            <el-step title="Configuration"></el-step>
            <el-step title="Confirmation"></el-step>
          </el-steps>

          <validation-observer ref='addstep_node' slim>
            <form role="addstep_node" v-show="formStep == 'node'" autocomplete="off" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <h3 class="mb-2">Node Details</h3>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Node Address -->
                  <base-input 
                    vid="node_address"
                    rules="required"
                    name="node address"
                    placeholder="Node Address"
                    v-model="model.node_address"
                    autocomplete="off">
                  </base-input>
                </b-col>

                <b-col>
                  <!-- Probe Address -->
                  <base-input
                    vid="probe_address"
                    rules="required|integer|min:0"
                    name="probe address"
                    @blur="runPrechecks"
                    placeholder="Probe Address" v-model="model.probe_address">
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Commissioning Date -->
                  <base-input name="date" rules="required" vid="commissioning_date">
                    <flat-picker slot-scope="{focus, blur}"
                      @on-open="focus"
                      @on-close="blur"
                      placeholder="Commissioning Date"
                      class="form-control datepicker"
                      v-model="model.commissioning_date">
                    </flat-picker>
                  </base-input>
                </b-col>

                <b-col>
                  <!-- Field Zone ID -->
                  <base-input vid="zone" rules="integer" name="zone" placeholder="Field Zone ID" v-model="model.zone"></base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Field Name -->
                  <base-input vid="field_name" rules="required" name="field name" placeholder="Field Name" v-model="model.field_name"></base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Latitude -->
                  <base-input vid="latt" :rules="{ regex:/^-?\d+(\.\d{1,15})?$/ }" placeholder="Latitude" name="latitude" v-model="model.latt"></base-input>
                </b-col>

                <b-col>
                  <!-- Longitude -->
                  <base-input vid="lng" :rules="{ regex:/^-?\d+(\.\d{1,15})?$/ }" placeholder="Longitude" name="longitude" v-model="model.lng"></base-input>
                </b-col>

                <b-col>
                  <!-- Visual Coordinate Picker -->
                  <MapCoordsPicker
                    :fullWidthBtn="true"
                    :accessToken="$store.state.mapBoxAccessToken"
                    modalTitle="Pinpoint Coordinates"
                    buttonTitle="Set Coords"
                    :startPos="startPos"
                    @setCoords="setCoords">
                  </MapCoordsPicker>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Node Type -->
                  <base-input name="node type" rules="required" vid="node_type">
                    <el-select @change="nodeTypeChanged" v-model="model.node_type" filterable placeholder="Node Type">
                      <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Soil Moisture')" label="Soil Moisture" value="Soil Moisture"></el-option>
                      <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Nutrients')"     label="Nutrient"      value="Nutrients" v-if="isAdmin()"></el-option>
                      <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Well Controls')" label="Well Controls" value="Wells"></el-option>
                      <el-option :disabled="!userCan('Add', 'Node Config') && !userCan('Add', 'Meters')"        label="Meters"        value="Water Meter"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <!-- Weather Station Name -->
                  <base-input 
                    placeholder="W.Station Name"
                    name="wl_station_name"
                    vid="wl_station_name"
                    v-b-tooltip.hover.top title="Optionally specify the associated weather stations's name."
                    v-model="model.wl_station_name"></base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <!-- Entity -->
                  <base-input
                    name="company"
                    rules="required"
                    vid="company"
                    placeholder="Entity"
                    autocomplete="off">
                    <el-select
                      :disabled="!model.node_type"
                      @change="companyChanged"
                      v-model="model.company_id"
                      filterable
                      placeholder="Entity"
                      autocomplete="off">
                      <template v-if="model.node_type && Object.values(companies).length">
                        <template v-for="company in companies">
                          <el-option 
                            v-if="
                              userCan('Add', convertNodeTypeToSubsystem(model.node_type), company.id, 'C') ||
                              userCan('Add', 'Node Config', company.id, 'C')
                            "
                            :key="company.id"
                            :label="company.company_name"
                            :value="company.id">
                            <span>{{ company.company_name }}</span>
                          </el-option>
                        </template>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col>
                  <!-- Group -->
                  <base-input
                    name="group"
                    vid="group"
                    placeholder="Group"
                    v-b-tooltip.hover.top title="Optionally add this node to a security group."
                    autocomplete="off">
                    <el-select
                      :disabled="!model.node_type || !model.company_id"
                      v-model="model.group_id"
                      filterable
                      placeholder="Group"
                      autocomplete="off">
                      <template v-if="model.node_type && groups.length">
                        <template v-for="group in groups">
                          <el-option 
                            v-if="
                              userCan('Add', convertNodeTypeToSubsystem(model.node_type), group.id, 'G') ||
                              userCan('Add', 'Node Config', group.id, 'G')
                            "
                            :key="group.id"
                            :label="group.group_name"
                            :value="group.id">
                            <span>{{ group.group_name }}</span>
                          </el-option>
                        </template>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>

            </form>
          </validation-observer>

          <validation-observer ref='addstep_device' slim>
            <form role="addstep_device" v-show="formStep == 'device'" autocomplete="off" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <h3 class="mb-2">Configuration</h3>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <!-- Node Make (Manufacturer) -->
                  <base-input name="node make" rules="required" vid="node_make">
                    <el-select v-model="model.node_make" default-first-option filterable placeholder="Node Make">
                      <el-option label="LF-AG02" value="LF-AG02"></el-option>
                      <el-option label="DMT (Eagle)" value="DMT (Eagle)"></el-option>
                      <el-option label="Fieldwise" value="Fieldwise"></el-option>
                      <el-option label="Other" value="Other"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input name="node serial no" rules="required" placeholder="Node Serial No" vid="node_serial_number" v-model="model.node_serial_number"></base-input>
                </b-col>
              </b-row>

              <template v-if="model.node_type === 'Soil Moisture'">
                <b-row>
                  <b-col>
                    <base-input name="device type" rules="required" vid="hardware_management_id">
                      <el-select v-model="model.hardware_management_id" filterable placeholder="Device Type">
                        <el-option v-for="device in device_types" :key="device.id" :label="device.device_make" :value="device.id"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <!-- Device Serial Number -->
                    <base-input name="device serial no" rules="required" placeholder="Device Serial No" vid="device_serial_number" v-model="model.device_serial_number"></base-input>
                  </b-col>
                </b-row>
              </template>

              <template v-if="model.node_type === 'Nutrients'">
                <b-row>
                  <b-col>
                    <base-input name="device type" rules="required" vid="hardware_management_id">
                      <el-select v-model="model.hardware_management_id" filterable placeholder="Device Type">
                        <el-option v-for="device in device_types" :key="device.id" :label="device.device_make" :value="device.id"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="device serial no" rules="required" placeholder="Device Serial No" vid="device_serial_number" v-model="model.device_serial_number"></base-input>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col>
                    <base-input name="nutrient template" rules="required" vid="nutrient_template">
                      <el-select v-model="model.nutrient_template_id" filterable placeholder="Nutrient Template">
                        <el-option v-for="tpl in nutrient_templates" :key="tpl.id" :label="tpl.name" :value="tpl.id"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                  </b-col>
                </b-row>
              </template>

              <template v-if="model.node_type === 'Wells' || model.node_type === 'Water Meter'">
                <b-row>
                  <b-col>
                    <base-input name="device type" rules="required" vid="hardware_management_id">
                      <el-select v-model="model.hardware_management_id" filterable placeholder="Device Type">
                        <el-option v-for="device in device_types" :key="device.id" :label="device.device_make" :value="device.id"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input name="device serial no" rules="required" placeholder="Device Serial No" vid="device_serial_number" v-model="model.device_serial_number"></base-input>
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
                    <b-tr v-if="model.company_id && (model.company_id != $store.state.user_obj.company_id)">
                      <b-td><strong>Company</strong></b-td><b-td>{{ companies['"'+model.company_id.toString()+'"'].company_name }}</b-td>
                    </b-tr>
                    <b-tr><b-td><strong>Node Address</strong></b-td><b-td>{{ model.node_address }}</b-td></b-tr>
                    <b-tr><b-td><strong>Probe Address</strong></b-td><b-td>{{ model.probe_address }}</b-td></b-tr>
                    <b-tr><b-td><strong>Node Type</strong></b-td><b-td>{{ model.node_type }}</b-td></b-tr>
                    <b-tr><b-td><strong>Node Make</strong></b-td><b-td>{{ model.node_make }}</b-td></b-tr>
                    <b-tr v-if="model.hardware_management_id">
                      <b-td><strong>Device Make</strong></b-td><b-td>{{ device_types[model.hardware_management_id].device_make }}</b-td>
                    </b-tr>
                    <b-tr><b-td><strong>Node Serial No</strong></b-td><b-td>{{ model.node_serial_number }}</b-td></b-tr>
                    <b-tr><b-td><strong>Device Serial No</strong></b-td><b-td>{{ model.device_serial_number }}</b-td></b-tr>
                    <b-tr><b-td><strong>Commissioning Date</strong></b-td><b-td>{{ model.commissioning_date }}</b-td></b-tr>
                    <b-tr><b-td><strong>Field Name</strong></b-td><b-td>{{ model.field_name }}</b-td></b-tr>
                    <b-tr><b-td><strong>Zone</strong></b-td><b-td>{{ model.zone }}</b-td></b-tr>
                    <b-tr><b-td><strong>Latitude</strong></b-td><b-td>{{ model.latt }}</b-td></b-tr>
                    <b-tr><b-td><strong>Longitude</strong></b-td><b-td>{{ model.lng }}</b-td></b-tr>
                    <b-tr><b-td><strong>Weather Station</strong></b-td><b-td>{{ model.wl_station_name }}</b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

            </b-col>
          </b-row>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="mr-auto" @click="closeAddNodeModal()">Cancel</base-button>
          <base-button type="outline-primary" class="ml-auto" @click="prevStep()" v-show="formStep != 'node'">Previous</base-button>
          <base-button type="primary" @click="formStep == 'confirm' ? addNode() : nextStep()"> {{ formStep == 'confirm' ? 'Create' : 'Next' }}</base-button>
        </template>
        
      </b-modal>

      <!-- Import -->
      <b-modal v-if="userCan('Import', 'Node Config')" v-model="showImportModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Import Data</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref="importform" slim>
            <form role="form" autocomplete="nope" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <base-input label="Import Type" name="import type" rules="required" vid="type">
                    <el-select v-model="import_model.type" filterable placeholder="Import Type">
                      <el-option label="CSV File" value='csv' selected></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Data Format" name="data format" rules="required" vid="format">
                    <el-select v-model="import_model.format" filterable placeholder="Data Format">
                      <template v-for="(format, iidx) in importFormats">
                        <el-option v-if="format.admin == true ? isAdmin() : true" :label="format.label" :value="format.value" :key="iidx">
                          <span style="float: left; font-size: 13px;">
                            {{ format.label }}
                          </span>
                          <b-button size="sm" style="float: right; font-size: 13px; margin-right:0.25em"
                            @click="openFormatPreviewModal(format.label, format.preview)">
                          Info
                          </b-button>
                        </el-option>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <validation-provider v-slot="{ errors, validate }">
                  <base-input label="Data File" name="file" rules="required" vid="filedata">
                    <b-form-file
                      v-model="import_model.filedata"
                      @change="fileSelectionChange(validate)"
                      placeholder="Select or drop file here.."
                      drop-placeholder="Drop file here...">
                    </b-form-file>
                  </base-input>
                  </validation-provider>
                </b-col>
              </b-row>

              <b-row v-if="import_model.format && import_model.format != 'node_import'">
                <b-col md="6">
                  <base-input label="CSV Delimiter" name="delimiter" rules="required" vid="delimiter">
                    <el-select
                      v-model="import_model.delimiter"
                      filterable
                      placeholder="CSV Delimiter">
                      <el-option 
                        v-for="(item, didx) in delimiters"
                        :key="didx"
                        :label="item.label"
                        :value="item.value">
                      </el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md="6">
                  <base-input label="Date Timezone" name="timezone" rules="required" vid="timezone">
                    <el-select
                      v-if="timeZones.length"
                      v-model="import_model.timezone"
                      filterable
                      placeholder="Timezone">
                      <el-option 
                        v-for="item in timeZones"
                        :key="item._key"
                        :label="item._lab"
                        :value="item._key">
                      </el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md="6">
                  <transition name="fade">
                    <base-input v-if="import_model.format == 'partial_n'" 
                      label="Date Format" name="date format" rules="required" vid="date_format">
                      <el-select
                        v-if="dateFormats.length"
                        v-model="import_model.date_format"
                        filterable
                        placeholder="Date Format">
                        <el-option 
                          v-for="item in dateFormats"
                          :key="item._key"
                          :label="item._lab"
                          :value="item._key">
                        </el-option>
                      </el-select>
                    </base-input>
                  </transition>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <b-form-checkbox v-model="import_model.dryrun" :value="'true'" :unchecked-value="'false'">Dry run</b-form-checkbox>
                </b-col>
              </b-row>

              <b-row v-if="import_model.log">
                <b-col md>
                  <b-form-textarea
                    class="importlog"
                    ref="importlog"
                    v-model="import_model.log"
                    placeholder="Log output.."
                    readonly
                    plaintext
                    rows="10"
                    max-rows="10">
                  </b-form-textarea>
                </b-col>
              </b-row>

              <b-row v-if="import_model.progress">
                <b-col md>
                  <b-progress :max="100" height="2rem">
                    <b-progress-bar
                      :value="import_model.progress"
                      :label="import_model.progress + '%'"
                      :variant="import_model.variant"
                      :animated="!import_model.complete">
                    </b-progress-bar>
                  </b-progress>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <base-button type="outline-primary" class="ml-auto" @click="import_model.busy ? cancelImport() : closeImportModal()" :disabled="import_model.complete">
              Cancel
            </base-button>
            <base-button type="primary" @click="import_model.complete ? closeImportModal() : importFile()" :disabled="import_model.busy && !import_model.complete">
              {{ (import_model.busy && !import_model.complete) ? "Busy" : (import_model.complete ? "Done" : "Import") }}
            </base-button>
        </template>
      </b-modal>

      <b-modal v-if="userCan('Import', 'Node Config')" v-model="showFormatPreviewModal" size="xl" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">{{ previewModalTitle }}</h6>
        </template>

        <template #default="{ hide }">
          <CodeEditor
            v-model="previewModalContent"
            :read_only="true"
            :wrap_code="true"
            :hide_header="true"
            theme="light"
            width="100%"
            border_radius="0px"
            height="200px"
            :languages="[['markdown', 'Markdown']]"
            font_size="14px">
          </CodeEditor>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <base-button type="outline-primary" class="ml-auto" @click="closeFormatPreviewModal">
              Close
            </base-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-0" footer-classes="pb-2">
        <template slot="header">

          <base-button
            :disabled="
              !userCan('Add', 'Node Config') &&
              !userCan('Add', 'Soil Moisture') &&
              !userCan('Add', 'Nutrients') &&
              !userCan('Add', 'Well Controls') &&
              !userCan('Add', 'Meters') ||
              loading"
            @click.native="showAddNodeModal()"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Add a new hardware node to an existing entity."
            icon>
            Add Node
          </base-button>

          <base-button
            :disabled="!userCan('Import', 'Node Config') || loading"
            @click.native="openImportDataModal()"
            class="btn"
            type="primary"
            size="sm"
            v-b-tooltip.hover.top title="Import non-live data/metadata via CSV."
            icon>
            Import
          </base-button>

        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadHardwareConfig">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="companies && Object.values(companies).length > 1"
              class="fullwidth"
              clearable
              filterable
              placeholder="Filter by Entity.."
              v-model="filterEntity"
              @change="loadHardwareConfig">
              <el-option v-for="c in companies" :value="c.id" :label="c.company_name" :key="c.id"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadHardwareConfig"
                placeholder="Search...">
              </b-input>
            </base-input>
          </b-col>
        </b-row>

        <b-row class='nomargin'>
          <b-col md>
            <b-table
              striped
              bordered
              outlined
              small
              stacked="lg"
              responsive
              show-empty
              primary-key="node_address"
              no-local-sorting
              @sort-changed="sortingChanged"
              :fields="tableColumns"
              :items="tableData"
              :busy.sync="bInitialQuery">

              <template #cell(latt)="data">
                {{ data.value ? parseFloat(data.value).toFixed(6) : '' }}
              </template>

              <template #cell(lng)="data">
                {{ data.value ? parseFloat(data.value).toFixed(6) : '' }}
              </template>

              <template #cell(date_time)="data">
                <div :style="'color:' + calcLastReadingColor(data.item)">{{ data.value }}</div>
              </template>

              <template #cell()="data">
                {{ data.value }}
              </template>

              <template #cell(actions)="data" v-if="canAction">
                <div class="d-flex justify-content-center">
                  <b-button 
                    :disabled="
                      !userCan('Edit', 'Node Config', data.item.id, 'O') &&
                      !userCan('Edit', convertNodeTypeToSubsystem(data.item.node_type), data.item.id, 'O')
                    "
                    @click="handleConfigure(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm">
                  Configure
                  </b-button>

                  <b-button 
                    :disabled="!userCan('Reboot', 'Node Config', data.item.id, 'O')"
                    @click="handleReboot(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    title="Attempt to reboot this node."
                    size="sm">
                  Reboot
                  </b-button>

                  <b-button 
                    :disabled="
                      !userCan('Delete', 'Node Config', data.item.id, 'O') &&
                      !userCan('Delete', convertNodeTypeToSubsystem(data.item.node_type), data.item.id, 'O')"
                    @click="handleDelete(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    title="Remove this node from it's entity. Note: It's feed data would still remain in tact."
                    size="sm">
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
              Showing {{ Math.min(1 + (perPage * (currentPage-1)), totalRows) }} to {{ Math.min((perPage*(currentPage-1)) + perPage, totalRows)  }} of {{ totalRows }} entries
            </b-col>
            <b-col md>
              <b-pagination @input="loadHardwareConfig" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
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
import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';
import MapCoordsPicker from '../../custom_components/MapCoordsPicker';
import CodeEditor from 'simple-code-editor';

export default {

  mixins: [ mab_utils ],

  components: {
    CodeEditor,
    [Steps.name]:  Steps,
    [Step.name]:   Step,
    [Select.name]: Select,
    [Option.name]: Option,
    [OptionGroup.name]: OptionGroup,
    flatPicker,
    MapCoordsPicker
  },

  data() {
    return {

      loading: false,
      bInitialQuery: true,
      
      tableColumns: [
        {
          key: 'node_address',
          label: 'Node Address',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'node_type',
          label: 'Node Type',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'date_time',
          label: 'Last Update',
          sortable: true,
          tdClass: 'valign',
          sortByFormatted: true,
          formatter: (v, k, i) => {
            if(v == 'N/A'){ return null; }
            return v;
          }
        },
        {
          key: 'field_name',
          label: 'Field Name',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'latt',
          label: 'Latitude',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'lng',
          label: 'Longitude',
          sortable: true,
          tdClass: 'valign'
        }
      ],

      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: null,

      sortBy: this.isAdmin() ? 'node_address' : 'date_time',
      sortDir: 'desc',

      showNewNodeModal: false,
      showFormatPreviewModal: false,
      previewModalContent: '',

      model: {
        node_type: '', // Empty Default
        node_address: '',
        probe_address: '',
        latt: 0.0,
        lng: 0.0,
        coords_locked: 0,
        hardware_management_id: '', // Hardware Management FK
        node_make: '',
        commissioning_date: '',
        zone: '',
        node_serial_number: '',
        device_serial_number: '',
        company_id: null,

        field_name: '',
        nutrient_template_id: null, // for nutrient nodes only
        wl_station_name: '',
        group_id: null
      },

      import_model: {
        type: 'csv',
        format: 'aquacheck',
        delimiter: ',',
        filedata: '',
        log: '',
        progress: 0,
        busy: false,
        complete: false,
        variant: 'success',
        dryrun: 'true',
        timezone: this.$store.state.user_obj.timezone,
        date_format: 'df1' /* default */
      },

      showImportModal: false,
      device_types: [],
      
      companies: [],
      groups: [],

      nutrient_templates: [],

      formStep: 'node',
      formSteps: ['node', 'device', 'confirm'],
      formStepIndex: 0,
      formsValid: false,

      canAction: false,

      startPos: {
        lat: 0.0,
        lng: 0.0
      },

      timeZones: [],

      delimiters: [
        {
          label: 'Comma (,)',
          value: ','
        },
        {
          label: 'Semicolon (;)',
          value: ';'
        },
        {
          label: 'Tab (\t)',
          value: "\t"
        },
        {
          label: 'Space ( )',
          value: " "
        },
        {
          label: 'Pipe (|)',
          value: "|"
        },
      ],

      dateFormats: [
        {
          _key: 'df1',
          _lab: 'YYYY-MM-DD HH:mm:ss',
        },
        {
          _key: 'df2',
          _lab: 'YYYY/MM/DD HH:mm:ss',
        },
        {
          _key: 'df3',
          _lab: 'YYYY-MM-DD HH:mm',
        },
        {
          _key: 'df4',
          _lab: 'YYYY/MM/DD HH:mm'
        }
      ],

      importFormats: [
        {
          label: 'Node Import',
          value: 'node_import',
          admin: false,
          preview: 
            "Header Rows: 0\n" +
            "Max Columns: 7\n" +
            "Format: Node Address, Node Type, Entity Name, Node Make, Node Serial, Sensor Type Name, Device Serial"
        },
        {
          label: 'AC Logger (Raw)',
          value: 'aquacheck',
          admin: false,
          preview: 
            "Header Rows: 0\n" +
            "Max Columns: 7\n" +
            "Format: @LOAD, Device Serial, Probe Serial, Reading Date, SM Readings (HEX), Temp Readings (HEX), Aux Data (HEX)\n" +
            "Note: Device Serial and Probe Serial is concatenated to form the Node Address"
        },
        {
          label: 'Simple Format (T)',
          value: 'simple_t',
          admin: false,
          preview: 
            "Header Rows: 0\n" +
            "Max Columns: 32\n" +
            "Format: Node Address, Reading Date (e.g: 2022/5/31 8:42), SM Reading 1, ..., SM Reading 15, Temp Reading 1, ..., Temp Reading 15"
        },
        {
          label: 'Nutrients',
          value: 'partial_n',
          admin: true,
          preview: 
            "Header Rows: 0\n" +
            "Max Columns: 6\n" +
            "Format: Node Address, Reading Date, M3.1 Reading, M4.1 Reading, M5.1 Reading, M6.1 Reading"
        }
      ]
    }
  },
  methods: {
    
    loadHardwareConfig(sortByOverride = '', sortDirOverride = '')
    {
      this.loading = true;
      this.$axios.post(this.$store.state.baseUrl+"/api/hardwareconfigtable", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  sortByOverride ? sortByOverride : this.sortBy,
        sort_dir: sortDirOverride ? sortDirOverride : (this.sortDir ? 'desc' : 'asc')
      })
      .then(resp => {

        this.loading = false;
        this.bInitialQuery = false;
        this.companies = resp.data.entities;

        if(this.isAdmin() || this.userLimits('View', 'Node Config', 'C').length > 1){
          this.tableColumns.unshift({
            key: 'company_name',
            label: 'Entity',
            sortable: true,
            tdClass: 'valign'
          });
        }

        if(this.isAdmin() || this.userCan('Integrate', 'Entities')){
          this.tableColumns.push({
            key: 'integrations',
            label: 'Integrations',
            sortable: true,
            thClass: 'halign valign',
            tdClass: 'halign valign'
          });
        }

        if(this.isAdmin()                         ||
          this.userCan('Edit',   'Node Config')   ||
          this.userCan('Edit',   'Soil Moisture') ||
          this.userCan('Edit',   'Nutrients')     ||
          this.userCan('Edit',   'Well Controls') ||
          this.userCan('Edit',   'Meters')        ||
          this.userCan('Delete', 'Node Config')   ||
          this.userCan('Delete', 'Soil Moisture') ||
          this.userCan('Delete', 'Nutrients')     ||
          this.userCan('Delete', 'Well Controls') ||
          this.userCan('Delete', 'Meters')        ||
          this.userCan('Reboot', 'Node Config')
        ){
          this.canAction = true;
          this.tableColumns.push({
            key: 'actions',
            label: 'Actions',
            thClass: 'halign'
          });
        }

        this.tableData = resp.data.rows;
        this.totalRows = resp.data.total;

        if(this.totalRows == 0){ this.currentPage = 1; }

        this.loadEntities();

      });
    },

    loadEntities()
    {
      // convert selected node type to subsystem
      let subsystem = this.convertNodeTypeToSubsystem(this.model.node_type);
      if(subsystem){
        if(this.userCan('Add', 'Node Config')){
          this.$axios.post(this.$store.state.baseUrl+"/api/companies_list", {
            context: [
              { verb: 'Add', module: 'Node Config'   },
              { verb: 'Add', module: subsystem }
            ]
          }).then((resp) => {
            this.companies = resp.data.companies;
          });

        } else if(
          this.userCan('Add', 'Soil Moisture') ||
          this.userCan('Add', 'Nutrients')     ||
          this.userCan('Add', 'Well Controls') ||
          this.userCan('Add', 'Meters')
        ){
          this.$axios.post(this.$store.state.baseUrl+"/api/companies_list", {
            context: [
              { verb: 'Add', module: subsystem }
            ]
          }).then((resp) => {
            this.companies = resp.data.companies;
          });
        } else {
          console.log("User cannot add nodes");
        }
      }
    },

    loadGroups()
    {
      // convert selected node type to subsystem
      let subsystem = this.convertNodeTypeToSubsystem(this.model.node_type);
      if(subsystem){
        this.$axios.post(this.$store.state.baseUrl+"/api/groups_list", {
          /* Chosen company */
          company_id: this.model.company_id ? this.model.company_id : this.$store.state.user_obj.company_id,
          context: [
            { verb: 'Add', module: 'Node Config' },
            { verb: 'Add', module: subsystem }
          ]
        })
        .then((resp) => {
          this.groups = resp.data.groups;
        });
      } else {
        this.groups = [];
      }
    },

    loadSensorTypes()
    {
      this.$axios.post(this.$store.state.baseUrl+"/api/hardwaremanagementlist", {
        device_type: this.model.node_type,
        company_id: this.model.company_id
      })
      .then((resp) => {
        this.device_types = resp.data.hardware;
        if(Object.values(this.device_types).length == 0){
          let canAddSensors = this.userCan('View', 'Sensor Types') && this.userCan('Add', 'Sensor Types');
          swal.fire({
            title: 'No Device Types Found',
            html: 
              'No <strong>' + this.model.node_type + '</strong> device types exist for <strong>' + 
              this.companies['"'+this.model.company_id.toString()+'"'].company_name + '</strong>.<br>' + 
              (canAddSensors ? 'Please go to Sensor Types and add one.' : 'Please request help from your administrator.'),
            showCancelButton: true,
            confirmButtonText: canAddSensors ? 'Go to Sensor Types' : 'OK',
            cancelButtonText: 'Close',
            buttonsStyling: false,
            customClass: {
              cancelButton: 'btn btn-outline-primary',
              confirmButton: 'btn btn-primary'
            }
          }).then((result) => {
            if(result.isConfirmed){
              if(canAddSensors){
                this.$router.push("/sensor_types");
              }
            }
          })
        }
      });
    },

    loadTimezones()
    {
      this.$axios.get(this.$store.state.baseUrl+"/api/getTimezones")
      .then((response) => {
        let _timezones = JSON.parse(JSON.stringify(response.data.timezones));
        let _offsets = JSON.parse(JSON.stringify(response.data.offsets));
        if(_timezones && _timezones.length){
          for(var i = 0; i < _timezones.length; i++){
            this.timeZones.push({
              _key: i.toString(), 
              _lab: _timezones[i] + ' (' + _offsets[_timezones[i]] + ')'
            });
          }
          console.log(this.$store.state.user_obj.timezone);
          console.log(this.timeZones);
        }
      });
    },

    loadNutrientTemplates()
    {
      if(this.model.company_id && this.model.node_type == 'Nutrients'){
        this.$axios.get(this.$store.state.baseUrl+"/api/loadNutrientTemplates/" + this.model.company_id)
        .then((response) => {
          this.nutrient_templates = response.data.templates;
        });
      }
    },

    handleConfigure(index, row)
    {
      this.$router.push("/node_config/edit/" + row.node_address)
    },

    handleReboot(index, row)
    {
      swal.fire({
        title: 'Node Remote Reboot',
        text: `Please confirm node remote reboot attempt`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reboot',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.rebootNode(row);
        }
      });
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Node Deletion',
        text: `Please confirm node removal`,
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
          this.deleteNode(row);
        }
      });
    },

    addNode()
    {
      console.log(this.model);
      if(this.formsValid){
        this.loading = true;

        if(this.model.latt != 0 || this.model.lng != 0){
          this.model.coords_locked = 1;
        }

        this.$axios.post(this.$store.state.baseUrl+"/api/hardwareconfignew", this.model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'node_added'){
            this.loadHardwareConfig('id', 'desc');
            this.closeAddNodeModal();
            this.$notify({
              title: 'Success',
              message: 'New node added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.formsValid = false;
          } else if(resp.data.message == 'alreadyexists'){
            this.$notify({
              title: 'Error',
              message: 'Node already exists',
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
      }
    },

    deleteNode(row)
    {
      this.loading = true;
      this.$axios.post(this.$store.state.baseUrl + "/api/hardwareconfigdestroy", { node_address: row.node_address })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'node_removed'){
          this.loadHardwareConfig();
          this.$notify({
            title: 'Removed',
            message: 'Node was removed',
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
    },

    rebootNode(row)
    {
      this.loading = true;
      this.$axios.get(this.$store.state.baseUrl + "/api/hardwareconfigreboot/" + row.node_address)
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'initiated'){
          this.$notify({
            title: 'Node Remote Reboot',
            message: 'Node remote reboot attempt initiated',
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
    },

    showAddNodeModal()
    {
      this.showNewNodeModal = true;
    },

    closeAddNodeModal()
    {
      this.clearModel();
      this.showNewNodeModal = false;
    },

    // todo: add to utils
    clearModel()
    {
      this.formStep = 'node';
      this.formStepIndex = 0;

      this.model.node_address = '';
      this.model.probe_address = '';
      this.model.field_name = '';
      this.model.latt = 0.0;
      this.model.lng = 0.0;
      this.model.coords_locked = 0;

      this.model.zone = '';

      this.model.node_type = 'Soil Moisture'; // the Default
      this.model.hardware_management_id = ''; // Hardware Management FK
      this.model.node_make = '';

      this.model.commissioning_date = '';
      this.model.node_serial_number = '';
      this.model.device_serial_number = '';

      this.model.nutrient_template_id = null;

      this.model.wl_station_name = '';
      this.model.company_id = null;
      this.model.group_id = null;
    },

    nodeTypeChanged()
    {
      this.model.company_id = null;
      this.model.group_id = null;
      this.loadEntities();
    },

    companyChanged()
    {
      this.model.hardware_management_id = '';
      this.model.group_id = '';
      this.loadGroups();
      this.loadSensorTypes();
      this.loadNutrientTemplates();
    },

    openImportDataModal()
    {
      this.showImportModal = true;
      if(!this.timeZones.length){
        this.loadTimezones();
      }
    },

    nextStep()
    {
      this.$refs["addstep_" + this.formSteps[this.formStepIndex]].validate()
      .then(result => {
        if (!result) { this.formsValid = false; return; } 
        this.formsValid = true;
        if(this.formStepIndex < (this.formSteps.length-1)){
          this.formStepIndex++;
          this.formStep = this.formSteps[this.formStepIndex];
        }
      });
    },

    prevStep()
    {
      if(this.formStepIndex > 0){
        this.formStepIndex--;
        this.formStep = this.formSteps[this.formStepIndex];
      }
      this.$refs["addstep_" + this.formSteps[this.formStepIndex]].reset();
    },

    /* Imports */

    openFormatPreviewModal(title, content)
    {
      this.previewModalTitle = title + ' Format Preview';
      this.previewModalContent = content;
      this.showFormatPreviewModal = true;
    },

    closeFormatPreviewModal()
    {
      this.previewModalTitle = '';
      this.previewModalContent = '';
      this.showFormatPreviewModal = false;
    },

    resetImportNodeModel()
    {
      this.import_node_model = {
        type: 'csv',
        format: 'node_import',
        filedata: '',
        busy: false,
        log: '',
        progress: 0,
        complete: false,
        variant: 'success',
        dryrun: 'true'
      };
    },

    resetImportDataModel()
    {
      this.import_model = {
        type: 'csv',
        format: 'aquacheck',
        filedata: '',
        busy: false,
        log: '',
        progress: 0,
        complete: false,
        variant: 'success',
        dryrun: 'true',
        timezone: this.$store.state.user_obj.timezone,
        date_format: 'df1' /* default */
      };
    },

    closeImportModal()
    {
      this.resetImportDataModel();
      this.showImportModal = false;
    },

    cancelImport(log_it = true)
    {
      this.import_model.busy = false;
      this.import_model.complete = false;
      this.import_model.progress = 0;
      if(log_it){
        this.importDataLog("Canceling Data Import");
      }
    },

    fileSelectionChange(validate_func)
    {
      validate_func();
      this.import_model.busy = false;
      this.import_model.complete = false;
      this.import_model.progress = 0;
      this.importDataLog("New file selected", true);
    },

    importDataLog(message, replace = false)
    {
      if(replace){
        this.import_model.log = message + "\n";
      } else {
        this.import_model.log += message + "\n";
      }
      this.$nextTick(() => {
        if(this.$refs.importlog){
          this.$refs.importlog.$el.scrollTop = this.$refs.importlog.$el.scrollHeight;
        }
      });
    },

    importFile()
    {
      this.$refs.importform.validate()
      .then(success => {

        if (!success) { return; }

        this.loading = true;
        this.import_model.busy = true;
        this.importDataLog("STARTING IMPORT");
        this.importDataLog("Uploading file..");
        this.import_model.progress = 5;
        
        let fd = new FormData();
        fd.set('type', this.import_model.type );
        fd.set('format', this.import_model.format );
        fd.append('filedata', this.import_model.filedata );

        this.$axios.post(this.$store.state.baseUrl+"/api/dataImport", fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(async (resp) => {

          this.loading = false;
          let data = resp.data;

          if(data.status == 'file_uploaded'){
            this.importDataLog("File uploaded, starting import..");
          } else if(data.status == 'unsupported_file'){
            this.importDataLog("Unsupported File Uploaded, aborting..");
            this.cancelImport(false);
            return;
          } else if(data.status == 'access_denied'){
            this.importDataLog("Access Denied, aborting..");
            this.cancelImport(false);
            return;
          } else if(data.status == 'date_format_error'){
            this.importDataLog("Invalid Date Format Supplied, aborting..");
            this.cancelImport(false);
            return;
          } else if(data.status == 'invalid_csv'){
            this.importDataLog("Invalid CSV file supplied, aborting..");
            this.cancelImport(false);
            return;
          } else if(data.status == 'upload_error'){
            this.importDataLog("File upload error, aborting..");
            this.cancelImport(false);
            return;
          } else {
            this.importDataLog("Server-side error occurred, aborting..");
            this.cancelImport(false);
            return;
          }

          this.import_model.progress = 10;

          if(data.type && data.type == 'csv'){
            if(data.format && ['node_import', 'aquacheck', 'simple_t'].indexOf(data.format) > -1){

              let import_running = true;
              let stats = {
                cols:   [], // Column related errors
                dt:     [], // Date related errors
                sm:     [], // Soil Moisture related errors
                tmp:    [], // Temperature related errors
                dup:    [], // Duplicates
                orp:    [], // Orphans (Rows with no existing node)
                node:   [], // Missing Node IDs
                inv_pe: [], // Permission Error
                inv_na: [], // Invalid Node Address
                inv_nt: [], // Invalid Node Type
                inv_dt: [], // Invalid Device Type
                inv_et: [], // Invalid Entity
                inv_ef: [], // Empty Field
                ins:    []  // Successful inserts
              };

              while(import_running){
                this.loading = true;

                let lresp = await this.$axios.post(
                  this.$store.state.baseUrl+"/api/dataImport",
                  {
                    ticket: data.ticket,
                    offset: data.offset,
                    total:  data.total,
                    type:   data.type,
                    format: data.format,
                    delimiter: this.import_model.delimiter,
                    dryrun: this.import_model.dryrun,
                    timezone: this.import_model.timezone,
                    date_format: this.import_model.date_format
                  }
                );

                this.loading = false;
                let ldata = lresp.data;
                
                if(ldata.status == 'perm_error'){
                  this.importDataLog("Permission error occurred, aborting..");
                  this.import_model.progress = 0;
                  this.import_model.busy = false;
                  import_running = false;
                }

                // ERRORS
                if(ldata.status == 'general_error'){
                  this.importDataLog("Server-side error occurred, aborting..");
                  this.import_model.progress = 0;
                  this.import_model.busy = false;
                  import_running = false;
                }

                // UPDATE PROGRESS BAR
                this.import_model.progress = 10 + Math.floor((ldata.offset / ldata.total) * 90);
                data.offset = ldata.offset;

                // MERGE CURRENT BATCH STATS INTO stats

                if(ldata.stats && Object.keys(ldata.stats).length){
                  stats.cols = stats.cols.concat(ldata.stats.cols);
                  if(ldata.stats.dt){
                    stats.dt = stats.dt.concat(ldata.stats.dt);
                  }
                  if(ldata.stats.sm){
                    stats.sm = stats.sm.concat(ldata.stats.sm);
                  }
                  if(ldata.stats.tmp){
                    stats.tmp = stats.tmp.concat(ldata.stats.tmp);
                  }
                  if(ldata.stats.dup){
                    stats.dup = stats.dup.concat(ldata.stats.dup);
                  }
                  if(ldata.stats.orp){
                    stats.orp = stats.orp.concat(ldata.stats.orp);
                  }
                  if(ldata.stats.inv_pe){
                    stats.inv_pe = stats.inv_pe.concat(ldata.stats.inv_pe);
                  }
                  if(ldata.stats.inv_na){
                    stats.inv_na = stats.inv_na.concat(ldata.stats.inv_na);
                  }
                  if(ldata.stats.inv_nt){
                    stats.inv_nt = stats.inv_nt.concat(ldata.stats.inv_nt);
                  }
                  if(ldata.stats.inv_dt){
                    stats.inv_dt = stats.inv_dt.concat(ldata.stats.inv_dt);
                  }
                  if(ldata.stats.inv_et){
                    stats.inv_et = stats.inv_et.concat(ldata.stats.inv_et);
                  }
                  if(ldata.stats.inv_ef){
                    stats.inv_ef = stats.inv_ef.concat(ldata.stats.inv_ef);
                  }
                  if(ldata.stats.ins){
                    stats.ins = stats.ins.concat(ldata.stats.ins);
                  }
                }

                // PROCESS MISSING NODE NAMES
                if(ldata.stats && ldata.stats.length && ldata.stats.node && ldata.stats.node.length){
                  var i;
                  for(i = 0; i < ldata.stats.node.length; i++){
                    if(stats.node.indexOf(ldata.stats.node[i]) === -1) {
                      stats.node.push(ldata.stats.node[i]);
                    }
                  }
                }

                // FINISHED
                if(ldata.status == 'import_complete'){
                  this.importDataLog((this.import_model.dryrun == 'true' ? 'DRY-RUN' : 'IMPORT' ) + " COMPLETED");
                  this.importDataLog("SUCCESSFUL INSERTS          : " + stats.ins.length);
                  if(stats.orp.length){
                    this.importDataLog("(of which healthy)          : " + (stats.ins.length > 0 ? (stats.ins.length - stats.orp.length) : 0));
                    this.importDataLog("(of which orphans)          : " + stats.orp.length);
                  }
                  this.importDataLog("FAILED INSERTS              : " + (
                    stats.cols.length +
                    stats.dt.length +
                    stats.sm.length +
                    stats.tmp.length +
                    stats.dup.length +
                    stats.inv_pe.length +
                    stats.inv_na.length +
                    stats.inv_nt.length +
                    stats.inv_dt.length +
                    stats.inv_et.length +
                    stats.inv_ef.length
                  ));

                  if(stats.cols.length){
                    this.importDataLog("(due to missing columns)    : " + stats.cols.length);
                  }
                  if(stats.dt.length){
                    this.importDataLog("(due to date errors)        : " + stats.dt.length);
                  }
                  if(stats.sm.length){
                    this.importDataLog("(due to s.m field errors )  : " + stats.sm.length);
                  }
                  if(stats.tmp.length){
                    this.importDataLog("(due to temp. field errors) : " + stats.tmp.length);
                  }
                  if(stats.dup.length){
                    this.importDataLog("(due to duplicates)         : " + stats.dup.length);
                  }
                  if(stats.inv_pe.length){
                    this.importDataLog("(due to permission error.)  : " + stats.inv_pe.length);
                  }
                  if(stats.inv_na.length){
                    this.importDataLog("(due to invalid node addr.) : " + stats.inv_na.length);
                  }
                  if(stats.inv_nt.length){
                    this.importDataLog("(due to invalid node type)  : " + stats.inv_nt.length);
                  }
                  if(stats.inv_dt.length){
                    this.importDataLog("(due to invalid sens. type) : " + stats.inv_dt.length);
                  }
                  if(stats.inv_et.length){
                    this.importDataLog("(due to invalid entity)     : " + stats.inv_et.length);
                  }
                  if(stats.inv_ef.length){
                    this.importDataLog("(due to empty req. field)   : " + stats.inv_ef.length);
                  }
                  if(stats.node.length){
                    this.importDataLog("Missing nodes: " + stats.node.join());
                  }

                  import_running = false;
                  this.import_model.busy = false;
                  this.import_model.complete = true;
                }
              }
            }
          }
          this.loading = false;
        });

      }).catch(err => {
        this.import_model.busy = false;
        this.importDataLog("Server-side error occurred.");
      });
    },

    runPrechecks(e)
    {
      if(this.model.node_address && this.model.probe_address != ''){
        let node_address = this.model.node_address + '-' + this.model.probe_address;
        this.$axios.get(this.$store.state.baseUrl+"/api/hardwareconfigexists/" + node_address)
        .then((resp) => {
          if(resp.data && resp.data.node_exists){
            this.model.node_address = '';
            this.model.probe_address = '';
            this.$notify({
              title: 'Error',
              message: node_address + ' already exists',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else {
            this.tryAndGetCoords(node_address);
          }
        });
      }
    },

    /*
      Crazy idea: Try and see whether the node's data has already been received 
      in the raw data table (prior to the node having been added to MAB),
      get the latest lat/lng pair and inject those values into the form
    */

    tryAndGetCoords(node_address){

      if(node_address){
        this.$axios.get(this.$store.state.baseUrl+"/api/hardwareconfiglatestcoords/" + node_address)
        .then((resp) => {
          if(resp && resp.data && resp.data.status == 'found'){
            this.model.latt = resp.data.latt;
            this.model.lng  = resp.data.lng;
            this.startPos.lat = resp.data.latt;
            this.startPos.lng = resp.data.lng;
          }
        });
      }
    },

    setCoords(coords)
    {
      this.model.latt = coords.lat;
      this.model.lng  = coords.lng;
      this.startPos.lat = coords.lat;
      this.startPos.lng = coords.lng;
    },

    convertToString(value)
    {
      if (value === null || typeof value === 'undefined') {
        return '';
      } else if (value instanceof Object) {
        return Object.keys(value).sort().map(key => this.convertToString(value[key])).join(' ');
      } else {
        return String(value)
      }
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadHardwareConfig();
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadHardwareConfig();
  }
};
</script>
<style>
  .no-border-card .card-footer{
    border-top: 0;
  }
  .importlog {
    font-family:'Courier New';
    font-weight:bold;
    margin-bottom:1rem;
    font-size:.8rem;
  }

  .fade-enter-active, .fade-leave-active {
    transition: opacity .5s;
  }
  .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
    opacity: 0;
  }

</style>
