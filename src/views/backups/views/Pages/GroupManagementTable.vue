<template>
  <div class="content">
    <base-header class="pb-6" type="groups">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Group Management</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add New Node Group -->
      <b-modal v-model="showNewGroupModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Create New Group</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='addform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input vid="group_name" rules="required|max:50" name="group name" placeholder="Group Name" v-model="add_model.group_name"></base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <base-input name="company" rules="required" vid="company">
                    <el-select v-model="add_model.company" filterable placeholder="Entity" :disabled="!isAdmin() && companies.length < 2" value-key="id">
                      <el-option v-for="company in companies" :key="company.id" :label="company.label" :value="company"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <base-input name="subsystem" rules="required" vid="subsystem">
                    <el-select
                      @visible-change="filterChanges('subsystem', add_model)"
                      v-model="add_model.subsystem"
                      filterable
                      placeholder="Module"
                      value-key="id">
                      <el-option v-for="subsystem in subsystems" :key="subsystem.id" :label="subsystem.subsystem_name" :value="subsystem"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <base-input vid="group_members" name="group members">

                    <el-select v-model="add_model.group_members" multiple filterable placeholder="Group Members" value-key="id">

                      <template v-if="add_model.subsystem && ['Map', 'Field Management', 'Dashboard', 'Node Config'].includes(add_model.subsystem.subsystem_name)">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == add_model.company.id">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Soil Moisture'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == add_model.company.id && node.meta == 'Soil Moisture'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Nutrients'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == add_model.company.id && node.meta == 'Nutrients'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Well Controls'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == add_model.company.id && node.meta == 'Wells'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Meters'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == add_model.company.id && node.meta == 'Water Meter'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Sensor Types'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Sensor Types'">
                          <template v-for="sensor in sensors">
                            <el-option :key="sensor.id" :label="sensor.label" :value="sensor" v-if="sensor.company_id == add_model.company.id">
                              <span style="float: left">{{ sensor.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ sensor.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Cultivars'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Cultivars'">
                          <template v-for="cv in cultivars" >
                            <el-option :key="cv.id" :label="cv.label" :value="cv" v-if="cv.company_id == add_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Cultivar Stages'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Cultivar Stages'">
                          <template v-for="cs in ct_stages">
                            <el-option :key="cs.id" :label="cs.label" :value="cs" v-if="cs.company_id == add_model.company.id">
                              <span style="float: left">{{ cs.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ cs.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Cultivar Templates'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Cultivar Templates'">
                          <template v-for="ct in ct_templates">
                            <el-option :key="ct.id" :label="ct.label" :value="ct" v-if="ct.company_id == add_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Nutrient Templates'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Nutrient Templates'">
                          <template v-for="nut in nut_templates">
                            <el-option :key="nut.id" :label="nut.label" :value="nut" v-if="nut.company_id == add_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Users'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Users'">
                          <template v-for="user in users">
                            <el-option :key="user.id" :label="user.label" :value="user" v-if="user.company_id == add_model.company.id">
                              <span style="float: left">{{ user.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ user.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Roles'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Roles'">
                          <template v-for="role in roles">
                            <el-option :key="role.id" :label="role.label" :value="role" v-if="role.company_id == add_model.company.id">
                              <span style="float: left">{{ role.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ role.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Security Rules'">
                        <el-option-group :label="companies['"'+add_model.company.id.toString()+'"'].label + ' Security Rules'">
                          <template v-for="rule in sec_rules">
                            <el-option :key="rule.id" :label="rule.label" :value="rule" v-if="rule.company_id == add_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="add_model.subsystem && add_model.subsystem.subsystem_name == 'Companies'">
                        <el-option-group label="Companies">
                          <template v-for="company in companies">
                            <el-option :key="company.id" :label="company.label" :value="company">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                    </el-select>

                  </base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeAddGroupModal">Cancel</b-button>
            <b-button variant="primary" @click='addGroup'>Create</b-button>
        </template>
      </b-modal>

      <!-- Edit Existing Node Group -->
      <b-modal v-model="showChangeGroupModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Edit Group</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='editform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input v-model="edit_model.group_name" vid="group_name" rules="required|max:50" name="group name" placeholder="Group Name"></base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <base-input vid="role" name="company" rules="required">
                    <el-select v-model="edit_model.company" filterable placeholder="Entity" disabled value-key="id">
                      <el-option v-for="company in companies" :key="company.id" :label="company.label" :value="company"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <base-input name="subsystem" rules="required" vid="subsystem">
                    <el-select
                      @visible-change="filterChanges('subsystem', edit_model)"
                      v-model="edit_model.subsystem"
                      filterable
                      placeholder="Module"
                      disabled
                      v-b-tooltip.hover.top title="Specify the group's module"
                      value-key="id">
                      <el-option v-for="subsystem in subsystems" :key="subsystem.id" :label="subsystem.subsystem_name" :value="subsystem"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <base-input vid="group_members" name="group members">

                    <el-select 
                      v-model="edit_model.group_members"
                      multiple
                      filterable
                      v-b-tooltip.hover.top title="Specify the members of this group"
                      placeholder="Group Members"
                      value-key="id">

                      <template v-if="edit_model.subsystem && ['Map', 'Field Management', 'Dashboard', 'Node Config'].includes(edit_model.subsystem.subsystem_name)">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == edit_model.company.id">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Soil Moisture'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == edit_model.company.id && node.meta == 'Soil Moisture'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Nutrients'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == edit_model.company.id && node.meta == 'Nutrients'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Well Controls'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == edit_model.company.id && node.meta == 'Wells'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Meters'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nodes'">
                          <template v-for="node in nodes">
                            <el-option :key="node.id" :label="node.label" :value="node" v-if="node.company_id == edit_model.company.id && node.meta == 'Water Meter'">
                              <span style="float: left">{{ node.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ node.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Sensor Types'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Sensor Types'">
                          <template v-for="sensor in sensors">
                            <el-option :key="sensor.id" :label="sensor.label" :value="sensor" v-if="sensor.company_id == edit_model.company.id">
                              <span style="float: left">{{ sensor.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ sensor.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Cultivars'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Cultivars'">
                          <template v-for="cv in cultivars" >
                            <el-option :key="cv.id" :label="cv.label" :value="cv" v-if="cv.company_id == edit_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Cultivar Stages'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Cultivar Stages'">
                          <template v-for="cs in ct_stages">
                            <el-option :key="cs.id" :label="cs.label" :value="cs" v-if="cs.company_id == edit_model.company.id">
                              <span style="float: left">{{ cs.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ cs.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Cultivar Templates'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Cultivar Templates'">
                          <template v-for="ct in ct_templates">
                            <el-option :key="ct.id" :label="ct.label" :value="ct" v-if="ct.company_id == edit_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Nutrient Templates'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Nutrient Templates'">
                          <template v-for="nut in nut_templates">
                            <el-option :key="nut.id" :label="nut.label" :value="nut" v-if="nut.company_id == edit_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Users'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Users'">
                          <template v-for="user in users">
                            <el-option :key="user.id" :label="user.label" :value="user" v-if="user.company_id == edit_model.company.id">
                              <span style="float: left">{{ user.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ user.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Roles'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Roles'">
                          <template v-for="role in roles">
                            <el-option :key="role.id" :label="role.label" :value="role" v-if="role.company_id == edit_model.company.id">
                              <span style="float: left">{{ role.label }}</span>
                              <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ role.meta }}</span>
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Security Rules'">
                        <el-option-group :label="companies['"'+edit_model.company.id.toString()+'"'].label + ' Security Rules'">
                          <template v-for="rule in sec_rules">
                            <el-option :key="rule.id" :label="rule.label" :value="rule" v-if="rule.company_id == edit_model.company.id">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                      <template v-else-if="edit_model.subsystem && edit_model.subsystem.subsystem_name == 'Companies'">
                        <el-option-group label="Companies">
                          <template v-for="company in companies">
                            <el-option :key="company.id" :label="company.label" :value="company">
                            </el-option>
                          </template>
                        </el-option-group>
                      </template>

                    </el-select>

                  </base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeEditGroupModal">Cancel</b-button>
            <b-button variant="primary" @click='updateGroup'>Save</b-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-0" footer-classes="pb-2">
        <template slot="header">

          <base-button 
            :disabled="!userCan('Add', 'Groups') || loading"
            @click="showAddGroupModal"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Groups are used to provide fine-grained access control to resources. They are used with Security Rules."
            icon>
            Add Group
          </base-button>

          <base-button
            :disabled="!userCan('View', 'Roles') || loading"
            @click.native="goToRoles()"
            class="btn"
            type="primary"
            size="sm"
            v-b-tooltip.hover.top title="Navigate back to the roles module."
            icon>
            Manage Roles
          </base-button>

        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadGroups">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="companies && Object.keys(companies).length && Object.keys(companies).length > 1"
              class="fullwidth"
              clearable
              filterable
              placeholder="Filter by Entity.."
              v-model="filterEntity"
              @change="loadGroups">
              <el-option v-for="c in companies" :value="c.id" :label="c.label" :key="c.id"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input 
                v-model="filterText"
                :debounce="1000"
                @update="loadGroups"
                placeholder="Search...">
              </b-input>
            </base-input>
          </b-col>
        </b-row>

        <b-row class='nomargin'>
          <b-col md>
            <b-table
              ref="table"
              striped
              bordered
              outlined
              small
              stacked="lg"
              responsive
              show-empty
              primary-key="id"
              @sort-changed="sortingChanged"
              :fields="tableColumns"
              :items="tableData"
              :busy.sync="bInitialQuery">

              <template #cell()="data">
                {{ data.value }}
              </template>

              <template #cell(group_members)="data">
                <div class='group_members_cell'>
                  <b-button variant="primary" size="sm" v-for="(i, index) in data.item.group_members" :key="index">{{ i.label }}</b-button>
                </div>
              </template>

              <template #cell(actions)="data" v-if="canAction">
                <div class="d-flex justify-content-center">
                  <b-button :disabled="!userCan('Edit', 'Groups', data.item.id, 'O')"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    title="Edit this group"
                    size="sm">
                  Edit
                  </b-button>

                  <b-button :disabled="!userCan('Delete', 'Groups', data.item.id, 'O')"
                    @click="handleDelete(data.index, data.item)"
                    title="Delete this group"
                    class="btn"
                    variant="outline-primary"
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
              <b-pagination @input="loadGroups" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
            </b-col>
          </b-row>
        </div>

      </card>
    </b-container>
  </div>
</template>
<script>
import { Select, Option, OptionGroup } from 'element-ui';
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    [OptionGroup.name]: OptionGroup
  },

  data() {
    return {
      loading: false,
      tableLoading: false,
      bInitialQuery: true,
      tableColumns: [
        {
          key: 'group_name',
          label: 'Group Name',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'subsystem.subsystem_name',
          label: 'Module',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'group_members',
          label: 'Group Members',
          sortable: true,
          thStyle: {
            width: '40% !important'
          },
          tdClass: 'valign'
        }
      ],

      nodes: [],
      sensors: [],
      cultivars: [],
      ct_stages: [],
      ct_templates: [],
      nut_templates: [],
      users: [],
      roles: [],
      groups: [],
      sec_rules: [],
      
      companies: [],
      subsystems: [],

      tableData: [],

      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: '',

      sortBy: this.isAdmin() || (this.companies && this.companies.length > 1) ? 'company_name' : 'group_name',
      sortDir: 'asc',

      showNewGroupModal: false,
      showChangeGroupModal: false,

      add_model: {
        id: null,
        group_name: '',
        company: { id: null, label: '' },
        subsystem: { id: null, subsystem_name: ''},
        group_members: []
      },

      edit_model: {
        id: null,
        group_name: '',
        company: { id: null, label: '' },
        subsystem: { id: null, subsystem_name: ''},
        group_members: []
      },
      canAction: false
    }
  },
  methods: {
    
    loadGroups() /* was loadNodeGroups */
    {
      this.loading = true;

      this.$axios.post("/api/groups", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'asc' : 'desc'
      })
      .then(resp => {

        this.loading = false;
        this.bInitialQuery = false;

        if(this.isAdmin() || this.userLimits('View', 'Groups', 'C').length > 1){
          this.tableColumns.unshift({
            key: 'company.company_name',
            label: 'Entity',
            sortable: true,
            tdClass: 'valign'
          });
        }

        if(this.userCan('Edit', 'Groups') || this.userCan('Delete', 'Groups')){
          this.canAction = true;
          this.tableColumns.push({
            key: 'actions',
            label: 'Actions',
            thClass: 'halign'
          });
        }

        this.tableData  = resp.data.groups_data;
        this.nodes      = resp.data.nodes;
        this.sensors    = resp.data.sensors;
        this.cultivars  = resp.data.cultivars;

        this.ct_stages  = resp.data.ct_stages;
        this.ct_templates = resp.data.ct_templates;
        this.nut_templates = resp.data.nut_templates;
        
        this.users      = resp.data.users;
        this.roles      = resp.data.roles;

        this.sec_rules  = resp.data.sec_rules;
        this.subsystems = resp.data.subsystems;
        this.companies  = resp.data.companies;

        this.totalRows  = resp.data.total;

        if(this.totalRows == 0){ this.currentPage = 1; }

      });
    },

    handleEdit(index, row)
    {
      this.edit_model = JSON.parse(JSON.stringify(row)); /* deep copy */
      this.showEditGroupModal();
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Group Deletion',
        text: `Please confirm group removal`,
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
          this.deleteGroup(row);
        }
      });
    },

    addGroup()
    {
      this.$refs.addform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        this.$axios.post("/api/group_add", this.add_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'group_added'){
            
            this.closeAddGroupModal();
            this.loadGroups();

            this.$notify({
              title: 'Success',
              message: 'New group added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.addform.setErrors(err.response.data.errors);
          }
        });
      });
    },

    updateGroup()
    {
      this.$refs.editform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        this.$axios.post("/api/group_update", this.edit_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'group_updated'){

            this.closeEditGroupModal();
            this.loadGroups();

            this.$notify({
              title: 'Success',
              message: 'Group updated',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.editform.setErrors(err.response.data.errors);
          }
        });
      });
    },

    deleteGroup(row)
    {
      this.loading = true;
      this.$axios.post("/api/group_destroy", { id: row.id })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'group_removed'){

          this.loadGroups();
          
          this.$notify({
            title: 'Removed',
            message: 'Group was removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    showAddGroupModal()
    {
      this.showNewGroupModal = true;
    },

    closeAddGroupModal()
    {
      this.resetAddModel();
      this.showNewGroupModal = false;
    },

    showEditGroupModal()
    {
      this.showChangeGroupModal = true;
    },

    closeEditGroupModal()
    {
      this.showChangeGroupModal = false;
    },

    resetAddModel()
    {
      this.add_model.group_id = null;
      this.add_model.group_name = '';
      this.add_model.company = { id: null, label: ''},
      this.add_model.subsystem = { id: null, label: ''},
      this.add_model.group_members = [];
    },

    goToRoles()
    {
      this.$router.push({
        name: "roles_manage",
        params: {}
      });
    },

    filterChanges(filter, obj)
    {
      if(filter == 'subsystem'){
        obj.group_members.length = 0;
      }
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadGroups();
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadGroups();
  },
};
</script>
<style>
  .no-border-card .card-footer{
    border-top: 0;
  }

  .members_col {
    max-width:50%;
  }

  .group_members_cell {
    display:flex;
    flex-flow:wrap;
  }

  .group_members_cell .btn {
    margin-bottom:0.5rem;
  }

</style>
