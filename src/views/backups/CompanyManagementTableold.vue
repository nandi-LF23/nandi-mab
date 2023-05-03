<template>
  <div class="content">
    <base-header class="pb-6" type="company">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Entity Management</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add new Company/Entity Modal -->
      <b-modal v-model="showCompanyCreationModal" size='md' centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Create New Entity</h6>
        </template>

        <template #default="{ hide }">
          <el-steps :active="formStepIndex" finish-status="success" align-center>
            <el-step title="Basic Info"></el-step>
            <el-step title="Physical Address"></el-step>
            <el-step title="Billing Address"></el-step>
            <el-step title="Modules"></el-step>
            <!-- <el-step title="Roles (Optional)"  v-if="isAdmin()"></el-step> -->
            <!-- <el-step title="Groups (Optional)" v-if="isAdmin()"></el-step> -->
            <el-step title="Confirmation"></el-step>
          </el-steps>

          <validation-observer ref='addstep_basic' slim>
            <form role="addstep_basic" v-show="formStep == 'basic'" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h3>Basic Info</h3>
                  <base-input label="Entity Name" name="entity name" rules="required" placeholder="Entity Name" vid="company_name" v-model="model.company_name"></base-input>
                  <base-input label="Entity Logo" name="entity logo">
                    <ImageCropUpload
                      :stencilProps="{ scalable: true, movable: true/*, aspectRatio: 2/1*/ }"
                      :resize="{ height: 32, width: 'auto' }"
                      @clear="clearLogo" @done="setLogo"/>
                  </base-input>
                  <base-input label="Contact Name"  name="contact name"  placeholder="Contact Name" vid="contact_name"   v-model="model.contact_name" rules="required">
                  </base-input>
                  <base-input label="Contact Email" name="contact email" placeholder="Contact Email" vid="contact_email" v-model="model.contact_email"></base-input>
                  <base-input label="Contact Phone" name="contact phone" placeholder="Contact Phone" vid="contact_phone" v-model="model.contact_phone"></base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>

          <validation-observer ref='addstep_paddr' slim>
            <form role="addstep_paddr" v-show="formStep == 'paddr'" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h3>Physical Address</h3>
                  <base-input name="physical address line 1" placeholder="Physical Address Line 1" vid="physical_address.line_1"     v-model="model.physical_address.line_1"></base-input>
                  <base-input name="physical address line 2" placeholder="Physical Address Line 2" vid="physical_address.line_2"     v-model="model.physical_address.line_2"></base-input>
                  <base-input name="physical city"           placeholder="Physical Town/City"      vid="physical_address.city"       v-model="model.physical_address.city"></base-input>
                  <base-input name="physical postal code"    placeholder="Physical Postal Code"    vid="physical_address.postalcode" v-model="model.physical_address.postalcode"></base-input>

                  <base-input name="physical country"        placeholder="Physical Country"        vid="physical_address.country">
                    <el-select
                      v-model="model.physical_address.country"
                      filterable
                      placeholder="Country">
                      <el-option
                        v-for="item in countries"
                        :key="item.code"
                        :label="item.name"
                        :value="item.code">
                      </el-option>
                    </el-select>
                  </base-input>

                </b-col>
              </b-row>
            </form>
          </validation-observer>

          <validation-observer ref='addstep_baddr' slim>
            <form role="addstep_baddr" v-show="formStep == 'baddr'" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h3>Billing Address</h3>
                  <base-input>
                    <b-form-checkbox v-model="model.p_as_b" name="p_as_b" value="yes" unchecked-value="no">
                      Same as Physical?
                    </b-form-checkbox>
                  </base-input>
                  <base-input name="billing address line 1" placeholder="Billing Address Line 1" vid="billing_address.line_1"     v-model="model.p_as_b == 'yes' ? model.physical_address.line_1     : model.billing_address.line_1"     :disabled="model.p_as_b == 'yes'"></base-input>
                  <base-input name="billing address line 2" placeholder="Billing Address Line 2" vid="billing_address.line_2"     v-model="model.p_as_b == 'yes' ? model.physical_address.line_2     : model.billing_address.line_2"     :disabled="model.p_as_b == 'yes'"></base-input>
                  <base-input name="billing city"           placeholder="Billing Town/City"      vid="billing_address.city"       v-model="model.p_as_b == 'yes' ? model.physical_address.city       : model.billing_address.city"       :disabled="model.p_as_b == 'yes'"></base-input>
                  <base-input name="billing postal code"    placeholder="Billing Postal Code"    vid="billing_address.postalcode" v-model="model.p_as_b == 'yes' ? model.physical_address.postalcode : model.billing_address.postalcode" :disabled="model.p_as_b == 'yes'"></base-input>

                  <base-input name="billing country"        placeholder="Billing Country"        vid="billing_address.country">
                    <el-select
                      v-model="model.p_as_b == 'yes' ? model.physical_address.country : model.billing_address.country"
                      :disabled="model.p_as_b == 'yes'"
                      filterable
                      placeholder="Country">
                      <el-option
                        v-for="item in countries"
                        :key="item.code"
                        :label="item.name"
                        :value="item.code">
                      </el-option>
                    </el-select>
                  </base-input>

                </b-col>
              </b-row>
            </form>
          </validation-observer>

          <validation-observer ref='addstep_modules' slim>
            <form role="addstep_modules" v-show="formStep == 'modules'" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h3>Modules</h3>
                  <base-input name="modules" vid="modules" rules="required">
                    <b-form-checkbox size="lg" v-model="model.modules" unchecked-value="" value="Soil Moisture">Soil Moisture</b-form-checkbox>
                    <b-form-checkbox v-if="isAdmin()" size="lg" v-model="model.modules" unchecked-value="" value="Nutrients">Nutrients</b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.modules" unchecked-value="" value="Well Controls">Well Controls</b-form-checkbox>
                    <b-form-checkbox size="lg" v-model="model.modules" unchecked-value="" value="Meters">Meters</b-form-checkbox>
                  </base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>

          <!-- <validation-observer ref='addstep_roles' slim v-if="isAdmin()">
            <form role="addstep_roles" v-show="formStep == 'roles'" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h3>Add Roles (Optional)</h3>
                  <base-input name="roles" vid="roles">
                    <el-select
                      v-model="model.add_roles"
                      multiple
                      filterable
                      allow-create
                      default-first-option
                      placeholder="Enter Role Names">
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>

          <validation-observer ref='addstep_groups' slim v-if="isAdmin()">
            <form role="addstep_groups" v-show="formStep == 'groups'" @submit.prevent="()=>false">

              <b-row><b-col><h3>Add Groups (Optional)</h3></b-col></b-row>

              <b-row class='group_row' v-for="(group, index) in model.add_groups" :key="index" align-v="center">
                <b-col md>
                  <div class='group_name'>{{ group.group_name }}</div>
                </b-col>
                <b-col md>
                  <div class='subsystem_name' v-if="subsystems">{{ subsystems[group.subsystem_id].subsystem_name }}</div>
                </b-col>
                <b-col md class='d-flex justify-content-end'>
                  <base-button
                    @click="removeGroup(index)"
                    class="btn"
                    type="primary"
                    size="sm"
                    icon>
                    Remove
                  </base-button>
                </b-col>
              </b-row>

              <b-row align-v="center">
                <b-col md>
                  <base-input name="group_name" placeholder="Group name" vid="group_name" v-model="add_group.group_name"></base-input>
                </b-col>
                <b-col md>
                  <base-input name="subsystem" vid="subsystem">
                    <el-select
                      v-if="subsystems"
                      v-model="add_group.subsystem_id"
                      value-key="id"
                      filterable
                      placeholder="Module">
                      <el-option
                        v-for="item in subsystems"
                        :key="item.id"
                        :label="item.subsystem_name"
                        :value="item.id">
                      </el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md class='d-flex justify-content-end'>
                  <base-input>
                    <base-button @click="addGroup"
                      class="btn"
                      type="primary"
                      size="sm"
                      icon>
                      Add Group
                    </base-button>
                  </base-input>
                </b-col>
              </b-row>

            </form>
          </validation-observer> -->

          <b-row v-show="formStep == 'confirm'">
            <b-col>
              <h3>Confirmation</h3>

              <b-row>
                <b-col>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Entity Name</strong></b-td><b-td><strong>Logo?</strong></b-td></b-tr>
                    <b-tr><b-td>{{ model.company_name }}</b-td><b-td>{{ model.company_logo ? 'Yes' : 'No' }}</b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Contact Name</strong></b-td><b-td><strong>Contact Email</strong></b-td><b-td><strong>Contact Phone</strong></b-td></b-tr>
                    <b-tr><b-td>{{ model.contact_name }}</b-td><b-td>{{ model.contact_email }}</b-td><b-td>{{ model.contact_phone }}</b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Address Details</strong></b-td><b-td><strong>Physical</strong></b-td><b-td><strong>Billing</strong></b-td></b-tr>
                    <b-tr><b-td>Line 1</b-td><b-td>{{ model.physical_address.line_1 }}</b-td><b-td>{{ model.p_as_b == 'yes' ? model.physical_address.line_1 : model.billing_address.line_1 }}</b-td></b-tr>
                    <b-tr><b-td>Line 2</b-td><b-td>{{ model.physical_address.line_2 }}</b-td><b-td>{{ model.p_as_b == 'yes' ? model.physical_address.line_2 : model.billing_address.line_2 }}</b-td></b-tr>
                    <b-tr><b-td>City/Town</b-td><b-td>{{ model.physical_address.city }}</b-td><b-td>{{ model.p_as_b == 'yes' ? model.physical_address.city  : model.billing_address.city }}</b-td></b-tr>
                    <b-tr><b-td>Postal Code</b-td><b-td>{{ model.physical_address.postalcode }}</b-td><b-td>{{ model.p_as_b == 'yes' ? model.physical_address.postalcode : model.billing_address.postalcode }}</b-td></b-tr>
                    <b-tr><b-td>Country</b-td><b-td>{{ model.physical_address.country }}</b-td><b-td>{{ model.p_as_b == 'yes' ? model.physical_address.country : model.billing_address.country }}</b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Modules</strong></b-td></b-tr>
                    <b-tr><b-td><span class='wswrap'>{{ model.modules.join(', ') }}</span></b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

              <b-row>
                <b-col md v-if='model.add_roles.length'>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Roles</strong></b-td></b-tr>
                    <b-tr><b-td><span class='wswrap'>{{ model.add_roles.join(', ') }}</span></b-td></b-tr>
                  </b-table-simple>
                </b-col>
                <b-col md v-if='model.add_groups.length'>
                  <b-table-simple responsive small>
                    <b-tr><b-td><strong>Groups</strong></b-td></b-tr>
                    <b-tr><b-td><span class='wswrap' v-if="subsystems">{{ model.add_groups.map(i => i.group_name + ' (' + subsystems[i.subsystem_id].subsystem_name + ')').join(', ') }}</span></b-td></b-tr>
                  </b-table-simple>
                </b-col>
              </b-row>

            </b-col>
          </b-row>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="mr-auto" @click="closeCreateCompanyModal()">Cancel</base-button>
          <base-button type="outline-primary" class="ml-auto" @click="prevStep()" v-show="formStep != 'basic'">Previous</base-button>
          <base-button type="primary" @click="formStep == 'confirm' ? createCompany() : nextStep()"> {{ formStep == 'confirm' ? 'Create' : 'Next' }}</base-button>
        </template>
      </b-modal>

      <!-- Company/Entity Removal Modal (with Move functionality) -->
      <b-modal v-model="showCompanyRemovalModal" size='md' centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Remove Entity: {{ delete_company_name }}</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='remcc' v-slots="{ validate, reset }" slim>
            <form class='cc_move_form' role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <h2>{{ move_model.move_company ? 'Relocation Configuration' : 'Please confirm Entity Removal' }} </h2>
                </b-col>
              </b-row>
              <b-row>
                <b-col md>
                  <!-- Move Entity Option -->
                  <b-form-checkbox size="lg" v-model="move_model.move_company" name="move_company" :value="true" :unchecked-value="false">
                    Relocate Entity Objects ?
                  </b-form-checkbox>
                </b-col>
              </b-row>
              <!-- Move Entity? -->
              <template v-if="move_model.move_company">
                <b-row>
                  <b-col md>
                    <!-- Choose Destination Entity -->
                    <base-input label="Destination Entity" rules="required" vid="destination_entity" name="destination entity">
                      <el-select
                        @change="getNewCompanyMoveMeta(move_model.new_company_id)"
                        v-model="move_model.new_company_id"
                        filterable
                        value-key="id"
                        placeholder="Choose Entity">
                        <template v-for="(cc, index) in move_model.target_companies">
                          <el-option :key="index" :label="cc.company_name" :value="cc.id"></el-option>
                        </template>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
                
                <b-row v-if="move_model.new_company_id">
                  <b-col md>
                    <base-input label="Choose Objects" rules="required" vid="move_types" name="chosen objects">
                      <b-form-group>
                        <b-form-checkbox-group
                          @change="reset"
                          size="lg"
                          v-model="move_model.move_types"
                          :options="move_model.movable_objects">
                        </b-form-checkbox-group>
                      </b-form-group>
                    </base-input>
                  </b-col>
                </b-row>
                <!-- Map Sensors -->
                <b-row v-if="move_model.new_company_id && move_model.move_types.indexOf('nodes') !== -1">
                  <b-col md>
                    <h3>Map Sensors</h3>
                    <b-table-simple
                      responsive
                      small
                      v-if="move_model.sensor_mappings.length > 0 && move_model.new_sensors.length > 0">
                      <b-tr>
                        <b-th><strong>{{ delete_company_name }}</strong></b-th>
                        <b-th>Device Type</b-th>
                        <b-th class='text-right'><strong>{{ move_model.target_companies['"'+move_model.new_company_id.toString()+'"'].company_name }}</strong></b-th>
                      </b-tr>
                      <template v-for="(sensormap,index) in move_model.sensor_mappings">
                        <b-tr :key="sensormap.old_sensor_id">
                          <template v-if="move_model.counts[sensormap.old_sensor_type]">
                            <b-td class='valign'>
                              {{ sensormap.old_sensor_name }}
                            </b-td>
                            <b-td class='valign'>
                              {{ sensormap.old_sensor_type }}
                            </b-td>
                            <b-td class='valign'>
                              <base-input
                                :vid="'new_sensors_'+index"
                                rules="required"
                                name="new sensor choice">
                                <el-select
                                  v-model="sensormap.new_sensor_id"
                                  value-key="id"
                                  size="small"
                                  filterable
                                  rules="required"
                                  placeholder="New Sensor">
                                  <el-option
                                    v-for="sensor in move_model.new_sensors"
                                    v-if="sensormap.old_sensor_type == sensor.device_type"
                                    :key="sensor.id"
                                    :label="sensor.device_make"
                                    :value="sensor.id">
                                  </el-option>
                                </el-select>
                              </base-input>
                            </b-td>
                          </template>
                          <template v-else>
                            <b-td>{{ sensormap.old_sensor_name }}</b-td>
                            <b-td>{{ sensormap.old_sensor_type }}</b-td>
                            <b-td class='text-right'>(to be relocated as-is)</b-td>
                          </template>
                        </b-tr>
                      </template>
                    </b-table-simple>
                    <p v-else>
                      <template v-if="move_model.sensor_mappings.length == 0">
                        No Active/Used Sensors to Map
                      </template>
                      <template v-else-if="move_model.new_sensors.length == 0">
                        <strong>{{ move_model.target_companies['"'+move_model.new_company_id.toString()+'"'].company_name }}</strong> has no sensors defined.<br>
                        <strong>{{ delete_company_name }}</strong>'s sensors will be moved over.
                      </template>
                    </p>
                  </b-col>
                </b-row>
                <!-- Map Roles -->
                <b-row v-if="move_model.new_company_id && move_model.move_types.indexOf('users') !== -1">
                  <b-col md>
                    <h3>Map User Roles</h3>
                    <b-table-simple responsive small v-if="move_model.role_mappings.length > 0 && move_model.new_roles.length > 0">
                      <b-tr>
                        <b-th>{{ delete_company_name }}</b-th>
                        <b-th></b-th>
                        <b-th class='text-right'>{{ move_model.target_companies['"'+move_model.new_company_id.toString()+'"'].company_name }}</b-th>
                      </b-tr>
                      <template v-for="(rolemap,index) in move_model.role_mappings">
                        <b-tr :key="rolemap.old_role_id">
                          <b-td class='valign'>
                            {{ rolemap.old_role_name }}
                          </b-td>
                          <b-td class='valign text-center'>
                            <b-icon icon="arrow-right-circle" animation='cyclon'></b-icon>
                          </b-td>
                          <b-td class='valign'>
                            <base-input
                              :vid="'new_roles_'+index"
                              rules="required"
                              name="new role choice">
                              <el-select
                                v-model="rolemap.new_role_id"
                                value-key="id"
                                size="mini"
                                filterable
                                rules="required"
                                placeholder="New Role">
                                <el-option
                                  v-for="role in move_model.new_roles"
                                  :key="role.id"
                                  :label="role.role_name"
                                  :value="role.id">
                                </el-option>
                              </el-select>
                            </base-input>
                          </b-td>
                        </b-tr>
                      </template>
                    </b-table-simple>
                    <p v-else>
                      <template v-if="move_model.role_mappings.length == 0">
                        No Active/Used Roles to Map
                      </template>
                      <template v-else-if="move_model.new_roles.length == 0">
                        <strong>{{ move_model.target_companies['"'+move_model.new_company_id.toString()+'"'].company_name }}</strong> has no roles defined.<br>
                        <strong>{{ delete_company_name }}</strong>'s roles will be relocated as-is.
                      </template>
                    </p>
                  </b-col>
                </b-row>
              </template>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="mr-auto" @click="closeCompanyRemovalModal()">Cancel</base-button>
          <base-button type="primary" @click="move_model.move_company ? moveCompanyThenDelete() : deleteCompany()">
            {{ move_model.move_company ? 'Relocate then Remove' : 'Remove' }}
            </base-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">
        
        <template slot="header">

          <base-button
            @click.native="showCreateCompanyModal()"
            :disabled="!userCan('Add', 'Entities') || loading"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="An Entity may contain various resources (Such as users, roles, nodes, sensors, etc) and is the top-level container object in MAB."
            icon>
            Create Entity
          </base-button>

          <base-button
            @click.native="goToDevicePlacementReporting()"
            :disabled="loading || (!isDistributor() && !isAdmin())"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Entity Related Reporting"
            icon>
            Reporting
          </base-button>

        </template>
        
        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadCompanies">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10" selected>10</b-form-select-option>
              <b-form-select-option :value="25">25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
          </b-col>
          <b-col md> 
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadCompanies"
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
              primary-key="id"
              no-local-sorting
              @sort-changed="sortingChanged"
              :fields="tableColumns"
              :items="tableData"
              :busy.sync="bInitialQuery">

              <template #cell()="data">
                {{ data.value }}
              </template>

              <template #cell(parent_entity)="data" v-if="isAdmin() || isDistributor()">
                <span v-if="data.value">{{ data.value}}</span>
                <b-icon v-else variant="success" icon="asterisk" title="Top Level Entity" aria-hidden="true"></b-icon>
              </template>

              <template #cell(is_distributor)="data" v-if="isAdmin() || isDistributor()">
                <b-icon variant="success" v-if="data.value" icon="check-circle" aria-hidden="true"></b-icon>
                <b-icon variant="success" v-else icon="circle" aria-hidden="true"></b-icon>
              </template>

              <template #cell(actions)="data" v-if="canAction">
                <div class="d-flex justify-content-center">
                  <b-button :disabled="!userCan('Edit', 'Entities', data.item.id, 'C')"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    size="sm"
                    variant="outline-primary"
                    icon>
                  Configure
                  </b-button>
                  <b-button :disabled="!userCan('Delete', 'Entities', data.item.id, 'C')"
                    @click="openCompanyRemovalModal(data.index, data.item)"
                    class="btn"
                    size="sm"
                    variant="outline-primary"
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
              <b-pagination @input="loadCompanies" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
            </b-col>
          </b-row>
        </div>

      </card>

    </b-container>
  </div>
</template>
<script>
import { Select, Option, Steps, Step } from 'element-ui';
import { parse } from 'date-fns';
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';
import ImageCropUpload from "../../custom_components/ImageCropUpload";
import countryList from "../../util/countryList";

export default {

  mixins: [ mab_utils ],

  components: {
    [Steps.name]:  Steps,
    [Step.name]:   Step,
    [Select.name]: Select,
    [Option.name]: Option,
    ImageCropUpload
  },

  data() {
    return {

      loading: false,
      bInitialQuery: true,

      countries: countryList,
      tableColumns: [
        {
          key: 'company_name',
          label: 'Entity',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'user_count',
          label: 'Users',
          tdClass: 'valign'
        },
        {
          key: 'role_count',
          label: 'Roles',
          tdClass: 'valign'
        },
        {
          key: 'group_count',
          label: 'Groups',
          tdClass: 'valign'
        },
        {
          key: 'node_count',
          label: 'Nodes',
          tdClass: 'valign'
        }
      ],
      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 10,
      filterText: '',
      sortBy: (this.isAdmin() || this.isDistributor()) ? 'parent_entity': 'company_name',
      sortDir: (this.isAdmin() || this.isDistributor()) ? 0 : 1,

      showCompanyCreationModal: false,
      showCompanyRemovalModal: false,

      delete_company_id: null,
      delete_company_name: '',

      model: {
        company_name: '',
        company_logo: '',

        p_as_b: 'no',

        physical_address: {
          line_1: '',
          line_2: '',
          city: '',
          postalcode: '',
          country: 'US'
        },

        billing_address: {
          line_1: '',
          line_2: '',
          city: '',
          postalcode: '',
          country: 'US'
        },
        
        contact_name: '',
        contact_email: '',
        contact_phone: '',

        modules: [],

        add_groups: [],
        add_roles: []
      },

      move_model: {
        move_company: false,
        old_company_id: null,
        new_company_id: null,
        sensor_mappings: [],
        role_mappings: [],
        new_sensors: [],
        counts: [],
        new_roles: [],
        move_types:['nodes','users'],
        target_companies: [],
        movable_objects: [
          { text: 'Nodes', value: 'nodes' },
          { text: 'Users', value: 'users' },
        ]
      },

      add_group: {
        group_name: '',
        subsystem_id: null
      },

      canAction: false,
      formStep: 'basic',
      formSteps: [],
      formStepIndex: 0,
      formsValid: false,

      totals: null
    };
  },

  methods: {

    loadCompanies()
    {
      // company name always visible on this page

      this.loading = true;

      this.$axios.post("/api/companies", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      }).then((resp) => {

        this.loading = false;

        if(this.bInitialQuery && (this.isAdmin() || this.isDistributor())){
          this.tableColumns.unshift({
            key: 'parent_entity',
            label: 'Parent Entity',
            sortable: true,
            tdClass: 'valign',
            thClass: 'valign'
          });
        }

        if(this.bInitialQuery && this.isAdmin()){
          this.tableColumns.push({
            key: 'is_distributor',
            label: 'Distributor',
            tdClass: 'halign valign',
            thClass: 'halign valign'
          });
        }

        if(this.bInitialQuery && (this.userCan('Edit', 'Entities') || this.userCan('Delete', 'Entities'))){
          this.canAction = true;
          this.tableColumns.push({
            key: 'actions',
            label: 'Actions',
            thClass: 'halign'
          });
        }

        this.tableData     = resp.data.rows;
        this.totalRows     = resp.data.total;
        this.totals        = resp.data.totals;

        if(this.totalRows == 0){ this.currentPage = 1; }

        if(this.bInitialQuery){
          this.formSteps = ['basic', 'paddr', 'baddr', 'modules'];
          //if(this.userCan('Add', 'Roles')){ this.formSteps.push('roles'); }
          //if(this.userCan('Add', 'Groups')){ this.formSteps.push('groups'); }
          //if(this.isAdmin()){ this.formSteps.push('roles'); }
          //if(this.isAdmin()){ this.formSteps.push('groups'); }
          this.formSteps.push('confirm');
        }

        for(var i = 0; i < this.tableColumns.length; i++){
          if(this.tableColumns[i].key == 'user_count'){
            this.tableColumns[i].label = 'Users (' + (resp.data.totals.user_count) + ")";
          } else
          if(this.tableColumns[i].key == 'role_count'){
            this.tableColumns[i].label = 'Roles (' + (resp.data.totals.role_count) + ")";
          } else
          if(this.tableColumns[i].key == 'group_count'){
            this.tableColumns[i].label = 'Groups (' + (resp.data.totals.group_count) + ")";
          } else
          if(this.tableColumns[i].key == 'node_count'){
            this.tableColumns[i].label = 'Nodes (' + (resp.data.totals.node_count) + ")";
          }
        }

        this.bInitialQuery = false;
      });
    },

    loadSubsystems()
    {
      this.$axios.get("/api/subsystems")
        .then((resp) => {
          this.subsystems = resp.data.subsystems;
      });
    },

    handleEdit(index, row)
    {
      this.$router.push({ name: 'entities_manage_edit', params: { id: row.id } });
    },

    goToDevicePlacementReporting()
    {
      this.$router.push({ name: 'entities_report', params: { report: 'device_usage' } });
    },

    openCompanyRemovalModal(index, row)
    {
      this.delete_company_id = row.id;
      this.delete_company_name = row.company_name;
      this.getOldCompanyMoveMeta(row.id);
      this.showCompanyRemovalModal = true;
    },

    closeCompanyRemovalModal()
    {
      this.delete_company_id = null;
      this.delete_company_name = null;
      this.clearCompanyMoveModel();
      this.showCompanyRemovalModal = false;
    },

    moveCompanyThenDelete()
    {
      this.$refs.remcc.validate()
      .then(result => {
        if (!result) { return; }

        this.loading = true;
        this.$axios.post("/api/company_move", this.move_model )
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'company_moved'){
            this.$notify({
              title: 'Status',
              message: 'Entity Objects Relocated',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });

            this.deleteCompany();
          }
        });
      });
    },

    createCompany()
    {
      console.log(this.model);
      if(this.formsValid){
        this.loading = true;
        this.$axios.post("/api/company_add", this.model)
        .then((resp) => {
          this.loading = false;

          if(resp.data.message == 'company_added'){
            // Refresh Companies after Add
            this.loadCompanies();
            this.clearModel()
            this.closeCreateCompanyModal();
            this.$notify({
              title: 'Success',
              message: 'New entity created',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.message == 'entity_exists'){
            this.$notify({
              title: 'Failure',
              message: 'Entity already exists',
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
      }
    },

    deleteCompany()
    {
      this.loading = true;
      this.$axios.post("/api/company_destroy", { id: this.delete_company_id })
      .then((resp) => {
        this.loading = false;

        if(resp.data.message == 'company_removed'){

          this.loadCompanies();

          this.$notify({
            title: 'Status',
            message: 'Entity Removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
          
        } else if(resp.data.message == 'company_in_use'){
          this.$notify({
            title: 'Failed',
            message: 'Cannot remove entity: ' + resp.data.object_count + ' ' + resp.data.object_type + ' still using it.',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        } else if(resp.data.message == 'company_missing'){
          this.$notify({
            title: 'Failed',
            message: "Cannot remove entity: Entity doesn't exist",
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }

        this.closeCompanyRemovalModal();

      });
    },

    getOldCompanyMoveMeta(old_cc_id)
    {
      this.loading = true;
      this.$axios.get("/api/company_move_om/" + old_cc_id)
      .then((resp) => {
        this.loading = false;
        if(resp.data){
          this.move_model.old_company_id = old_cc_id;
          this.move_model.sensor_mappings = resp.data.sensor_mappings;
          this.move_model.role_mappings = resp.data.role_mappings;
          this.move_model.target_companies = resp.data.target_companies;
        }
      }).catch(err => { this.loading = false; });
    },

    getNewCompanyMoveMeta(new_cc_id)
    {
      this.loading = true;
      this.$axios.get("/api/company_move_nm/" + new_cc_id)
      .then((resp) => {
        this.loading = false;
        if(resp.data){
          this.move_model.new_sensors = resp.data.new_sensors;
          this.move_model.counts = resp.data.counts;
          this.move_model.new_roles = resp.data.new_roles;
        }
      }).catch(err => { this.loading = false; });
    },

    clearCompanyMoveModel()
    {
      this.move_model.move_company = false;
      this.move_model.old_company_id = null;
      this.move_model.new_company_id = null;
      this.move_model.sensor_mappings = [];
      this.move_model.role_mappings = [];
      this.move_model.new_sensors = [];
      this.move_model.new_roles = [];
      this.move_model.move_types = [];
      this.move_model.target_companies = [];
      this.move_model.movable_objects = [
        { text: 'Nodes', value: 'nodes' },
        { text: 'Users', value: 'users' },
      ];
    },

    clearModel()
    {
      this.formStep = 'basic';
      this.formStepIndex = 0;

      this.model.company_name = '';
      this.model.company_logo = '';
      this.model.p_as_b = 'no';
      this.model.physical_address.line_1 = '';
      this.model.physical_address.line_2 = '';
      this.model.physical_address.city = '';
      this.model.physical_address.postalcode = '';
      this.model.physical_address.country = 'US';

      this.model.billing_address.line_1 = '';
      this.model.billing_address.line_2 = '';
      this.model.billing_address.city = '';
      this.model.billing_address.postalcode = '';
      this.model.billing_address.country = 'US';
        
      this.model.contact_name = '';
      this.model.contact_email = '';
      this.model.contact_phone = '';

      this.model.add_groups = [];
      this.model.add_roles = [];

      this.model.modules = [];
    },

    showCreateCompanyModal()
    {
      this.formStep = 'basic';
      this.formStepIndex = 0;
      this.showCompanyCreationModal = true;
    },

    closeCreateCompanyModal()
    {
      this.clearModel();
      this.showCompanyCreationModal = false;
      this.formStep = 'basic';
      this.formStepIndex = 0;
    },

    addGroup()
    {
      if(this.add_group.group_name && this.add_group.subsystem_id){
        this.model.add_groups.push(JSON.parse(JSON.stringify(this.add_group)));
        // clear model
        this.add_group.group_name = '';
        this.add_group.subsystem_id = null;
      }
    },

    removeGroup(index)
    {
      this.model.add_groups.splice(index, 1);
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

    setLogo(data)
    {
      console.log(data);
      this.model.company_logo = data;
    },

    clearLogo()
    {
      this.model.company_logo = '';
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadCompanies();
    }
  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadCompanies();
    this.loadSubsystems();
  }
};
</script>
<style>
  .vue-advanced-cropper__background, .vue-advanced-cropper__foreground {
    opacity:0.5;
  }
  .no-border-card .card-footer{
    border-top: 0;
  }
  .group_row .group_name, .group_row .subsystem_name {
    padding: .625rem .75rem;
    font-size:0.8em;
  }
  .group_row {
    margin-bottom:0.25em;
  }

  .cc_move_form table fieldset.form-group {
    margin-bottom:0;
  }

</style>