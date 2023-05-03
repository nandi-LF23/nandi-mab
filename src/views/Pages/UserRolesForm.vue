<template>
  <div class="content">
    <base-header class="pb-6" type="roles">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show="company_name && role_model.role_name">
            {{ 'Role Management - ' + company_name + ' - ' + role_model.role_name }}
          </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>

      <!-- Browse Security Templates -->
      <b-modal v-model="browseTemplatesModalVisible" centered no-close-on-esc no-close-on-backdrop size='lg'>

        <template #modal-header="{ close }">
          <h6 class="modal-title">Security Templates</h6>
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
                    <b-button :disabled="!userCan('Edit', 'Security Templates', data.item.id, 'O') || loading"
                      @click="confirmSecurityTemplateApply(data.index, data.item)"
                      class="btn"
                      variant="outline-primary"
                      size="sm"
                      icon>
                    Apply Template
                    </b-button>
                    <b-button :disabled="!userCan('Delete', 'Security Templates', data.item.id, 'O') || loading"
                      @click="confirmSecurityTemplateRemoval(data.index, data.item)"
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

      <!-- Save Security Template Dialog -->
      <b-modal v-model="saveTemplatesModalVisible" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 class="modal-title">Save Security Template</h6>
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
          <b-button variant="primary" @click="saveSecurityTemplate" :disabled="!saveTemplateName">
            Save
          </b-button>
        </template>
      </b-modal>

      <div class='row'>
        <div class='col-md-12'>

          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-0">Role Details</h3>
            </template>

            <div class="card-body">
              <validation-observer ref='form' slim>
                <form role="form" @submit.prevent="()=>false">
                  <b-row>
                    <b-col md>
                      <base-input @change="syncFields" name="role_name" rules="required" label="Name" placeholder="Name" vid="role_name" v-model="role_model.role_name"></base-input>
                    </b-col>
                  </b-row>
                </form>
              </validation-observer>
            </div>
          </card>

          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-0">Role Members <b-badge v-b-tooltip.hover.top title="Members of this role">{{ members.length }}</b-badge></h3>
            </template>
            <div class="card-body">
              <b-row>
                <b-col md>
                  <b-button
                    v-for="(item, index) in members"
                    :key="index"
                    :to="'/users_manage/edit/' + item.label"
                    :disabled="!userCan('Edit', 'Users', item.id, 'O')"
                    class="mb-2"
                    variant="success"
                    v-b-tooltip.hover.top :title="'Edit ' + item.label"
                    size="sm">
                    {{ item.label }}
                  </b-button>
                </b-col>
              </b-row>
            </div>
          </card>


          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-2">Role Permissions</h3>
              <b-row>
                <b-col md>

                  <base-button @click="showBrowseTemplatesModal"
                    :disabled="!userCan('View', 'Security Templates') || loading"
                    class="btn"
                    type="primary"
                    size="sm"
                    v-b-tooltip.hover.top title="Load a security template for this role. Warning: All existing role permissions would be replaced."
                    icon>
                    Load Template
                  </base-button>

                  <base-button @click="showSaveTemplatesModal" 
                    :disabled="!userCan('Add', 'Security Templates') || !userCan('Edit', 'Security Templates') || !security_rules || loading"
                    class="btn"
                    type="primary"
                    size="sm"
                    v-b-tooltip.hover.top title="Save the below role permissions as a reusable template (to apply to other roles)."
                    icon>
                    Save Template
                  </base-button>

                </b-col>
              </b-row>
            </template>
            <div class="card-body">
              <validation-observer ref='updaterules' slim>
                <template v-if="security_rules && security_rules.length">
                  <template v-for="rule in security_rules">
                    <b-row align-v="center" :key="rule.id">

                      <b-col md>
                        <base-input label="Module" name="module" rules="required" vid="subsystem">
                          <el-select
                            disabled
                            @change="filterChanges('subsystem', rule)"
                            v-model="rule.subsystem"
                            value-key="id"
                            filterable
                            placeholder="Choose">
                            <el-option
                              v-for="item in subsystem_meta"
                              :key="item.id"
                              :label="item.subsystem_name"
                              :value="item">
                            </el-option>
                          </el-select>
                        </base-input>
                      </b-col>

                      <b-col md>
                        <base-input label="Permissions" name="permissions" rules="required" vid="permissions">
                          <el-select
                            @change="filterChanges('permissions', rule)"
                            :disabled="!rule.subsystem"
                            v-model="rule.permissions"
                            value-key="id"
                            multiple
                            filterable
                            placeholder="Choose">
                            <template v-if="rule && rule.subsystem && rule.subsystem.id">
                              <el-option
                                v-for="item in subsystem_meta[rule.subsystem.id.toString()].permissions"
                                :key="item.id"
                                :label="item.permission_name"
                                :value="item">
                              </el-option>
                            </template>
                          </el-select>
                        </base-input>
                      </b-col>

                      <b-col md>
                        <base-input label="Limits" :disabled="!rule.permissions.length">
                          <el-select 
                            :disabled="!rule.permissions.length"
                            v-model="rule.limits"
                            value-key="id"
                            multiple
                            filterable
                            name="limits"
                            rules="required"
                            vid="limits"
                            placeholder="Choose">
                            <template v-if="rule && rule.subsystem && rule.subsystem.id">

                              <el-option-group label="Entities" v-if="subsystem_meta[rule.subsystem.id.toString()].limits.companies">
                                <template v-for="item in subsystem_meta[rule.subsystem.id.toString()].limits.companies">
                                  <el-option
                                    :key="item.id"
                                    :label="item.label"
                                    :value="item">
                                  </el-option>
                                </template>
                              </el-option-group>

                              <el-option-group
                                :label="subsystem_meta[rule.subsystem.id.toString()].subsystem_name + ' Groups'"
                                v-if="subsystem_meta[rule.subsystem.id.toString()].limits.groups">
                                <template v-for="item in subsystem_meta[rule.subsystem.id.toString()].limits.groups">
                                  <el-option
                                    :key="item.id"
                                    :label="item.label"
                                    :value="item">
                                    <span style="float: left">{{ item.label }}</span>
                                    <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ item.meta }}</span>
                                  </el-option>
                                </template>
                              </el-option-group>

                            </template>
                          </el-select>
                        </base-input>
                      </b-col>

                      <b-col md>

                        <base-button 
                          :disabled="!userCan('Edit', 'Security Rules') || loading"
                          @click.native="updateSecurityRule(rule)"
                          class="btn"
                          type="primary"
                          size="sm"
                          icon>
                          Update
                        </base-button>

                        <base-button
                          :disabled="!userCan('Delete', 'Security Rules') || loading"
                          @click.native="deletionPrompt(rule)"
                          class="btn"
                          type="primary"
                          size="sm"
                          icon>
                          Delete
                        </base-button>

                      </b-col>
                    </b-row>
                  </template>
                </template>

              </validation-observer>

              <validation-observer ref='addrules' slim>
                <b-row align-v="center" v-if="subsystem_meta">

                  <b-col md>
                    <base-input label="Module" name="module" rules="required" vid="subsystem">
                      <el-select
                        @change="filterChanges('subsystem', rule_model)"
                        v-model="rule_model.subsystem"
                        value-key="id"
                        filterable
                        v-b-tooltip.hover.top title="Choose a system module to which this security rule will apply to."
                        placeholder="Choose">
                        <el-option
                          v-for="item in subsystem_meta"
                          :key="item.id"
                          :label="item.subsystem_name"
                          :value="item">
                        </el-option>
                      </el-select>
                    </base-input>
                  </b-col>

                  <b-col md>
                    <base-input label="Permissions" name="permissions" rules="required" vid="permissions">
                      <el-select
                        @change="filterChanges('permissions', rule_model)"
                        :disabled="!rule_model.subsystem.id"
                        v-model="rule_model.permissions"
                        value-key="id"
                        multiple
                        filterable
                        v-b-tooltip.hover.top title="Choose the desired permissions to grant for the chosen module. Multiple permissions can be added per module."
                        placeholder="Choose">
                        <template v-if="rule_model && rule_model.subsystem && rule_model.subsystem.id">
                          <el-option
                            v-for="item in subsystem_meta[rule_model.subsystem.id.toString()].permissions"
                            :key="item.id"
                            :label="item.permission_name"
                            :value="item">
                          </el-option>
                        </template>
                      </el-select>
                    </base-input>
                  </b-col>

                  <b-col md>
                    <base-input label="Limits" :disabled="!rule_model.permissions.length">
                      <el-select 
                        :disabled="!rule_model.permissions.length"
                        v-model="rule_model.limits"
                        value-key="id"
                        multiple
                        filterable
                        name="limits"
                        rules="required"
                        vid="limits"
                        v-b-tooltip.hover.top title="Specify the entities/group(s) to which these permissions apply to."
                        placeholder="Choose">
                        <template v-if="rule_model && rule_model.subsystem && rule_model.subsystem.id">

                          <el-option-group label="Entities" v-if="subsystem_meta[rule_model.subsystem.id.toString()].limits.companies">
                            <template v-for="(item, index) in subsystem_meta[rule_model.subsystem.id.toString()].limits.companies">
                              <el-option
                                :key="item.id"
                                :label="item.label"
                                :value="item">
                              </el-option>
                            </template>
                          </el-option-group>

                          <el-option-group
                            :label="subsystem_meta[rule_model.subsystem.id.toString()].subsystem_name + ' Groups'"
                            v-if="subsystem_meta[rule_model.subsystem.id.toString()].limits.groups">
                            <template v-for="(item, index) in subsystem_meta[rule_model.subsystem.id.toString()].limits.groups">
                              <el-option
                                :key="item.id"
                                :label="item.label"
                                :value="item">
                                  <span style="float: left">{{ item.label }}</span>
                                  <span style="float: right; color: #8492a6; font-size: 13px; margin-right:1em">{{ item.meta }}</span>
                              </el-option>
                            </template>
                          </el-option-group>

                        </template>
                      </el-select>
                    </base-input>
                  </b-col>

                  <b-col md>
                    <base-button
                      :disabled="!userCan('Add', 'Security Rules') || loading"
                      @click.native="addSecurityRule"
                      class="btn"
                      type="primary"
                      size="sm"
                      v-b-tooltip.hover.top title="Add the security rule. Warning: changes would effect immediately."
                      icon>
                      Add Rule
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
import Modal from '@/components/Modal'
import { Select, Option, OptionGroup } from 'element-ui'
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    [OptionGroup.name]: OptionGroup,
    Modal
  },
  data() {
    return {
      loading: false,

      role_model: {
        id: null,
        role_name: ''
      },

      rule_model: {
        role_id: this.$route.params.id,
        subsystem: { id: null, subsystem_name: '' },
        permissions: [],
        limits: {
          companies: [],
          groups: []
        }
      },

      company_name: '',

      security_rules: null,
      subsystem_meta: null,
      members: [],

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
          key: 'company_name',
          label: 'Entity',
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
      filterText: ''

    };
  },
  methods: {

    loadRole()
    {
      this.loading = true;
      this.$axios.get("/api/role/" + this.$route.params.id)
      .then((resp) => {
        this.loading = false;
        this.role_model = resp.data.role;
        this.company_name = resp.data.role.company_name;
        this.members = resp.data.members;
      });
    },

    /* Load existing security rules */
    loadRules()
    {
      this.loading = true;
      return this.$axios.get("/api/role_rules_get/" + this.$route.params.id)
      .then((resp) => {
        this.loading = false;
        this.security_rules = resp.data.security_rules;
        this.subsystem_meta = resp.data.subsystem_meta;
        return resp;
      });
    },

    /* Load Saved Security Templates */
    loadTemplates()
    {
      this.loading = true;
      this.$axios.get("/api/sec_tpl_get")
      .then((resp) => {
        this.loading = false;
        this.loadedTemplates = resp.data.templates;
        this.totalRows = this.loadedTemplates.length;
      });
    },

    syncFields()
    {
      this.$refs.form.validate().then(success => {
        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please complete all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;

        this.$axios.post("/api/role_update", this.role_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'role_updated'){
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

    /* -------------- */
    /* Security Rules */
    /* ---------------*/

    // add a new security rule (along with subsystem, permission verbs, limit objects (companies/groups))
    addSecurityRule()
    {
      this.$refs.addrules.validate().then(success => {
        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please complete all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;

        this.$axios.post("/api/role_rule_add", this.rule_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'rule_added'){

            // reset model
            this.clearRuleModel(this.rule_model);

            // reset form fields
            this.$refs.addrules.reset();

            // reload rules after add
            this.loadRules();

            this.$notify({
              title: 'Added',
              message: 'Rule was added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.addrules.setErrors(err.response.data.errors);
          }
        });
      });
    },

    // update existing security rule
    updateSecurityRule(rule)
    {
      this.$refs.updaterules.validate().then(success => {
        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please complete all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;

        this.$axios.post("/api/role_rule_update", rule)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'rule_updated'){
            this.$notify({
              title: 'Updated',
              message: 'Rule was updated',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.updaterules.setErrors(err.response.data.errors);
          }
        });
      });
    },

    deletionPrompt(rule)
    {
      swal.fire({
        title: 'Are you sure?',
        text: 'Please confirm rule removal',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.deleteSecurityRule(rule);
        }
      });
    },

    // remove security rule
    deleteSecurityRule(rule)
    {
      this.loading = true;
      this.$axios.post("/api/role_rule_destroy", { id: rule.id })
      .then((response) => {
        this.loading = false;
        if(response.data.message == 'rule_removed'){

          this.loadRules();

          this.$notify({
            title: 'Removed',
            message: 'Rule was removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    // for enabling/disabling dropdowns
    filterChanges(filter, obj)
    {
      if(filter == 'subsystem'){
        while(obj.limits.length) obj.limits.pop();
        while(obj.permissions.length) obj.permissions.pop();
      } else if(filter == 'permissions'){
        // future logic
      } else if(filter == 'groups'){
        // future logic
      }
    },

    clearRuleModel(obj)
    {
      obj.role_id = this.$route.params.id;
      obj.subsystem = { id: null, subsystem_name: '' };
      obj.permissions = [];
      obj.limits = [];
    },

    resetValidation()
    {
      this.$nextTick(() => {
        this.$refs.form.reset();
        this.$refs.addrules.reset();
        this.$refs.updaterules.reset();
      });
    },

    /* ------------------ */
    /* Security Templates */
    /* ------------------ */

    /* Saves the existing set of defined security rules as a security template */
    /* NOTE: Limits are limited to companies only (due to groups being company specific for now) */
    saveSecurityTemplate()
    {
      this.$refs.savetpl.validate().then(success => {
        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please complete all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        // create template object
        let template = {
          name: this.saveTemplateName,
          rules: []
        };

        // process rules
        this.security_rules.forEach( (r) => {
          template.rules.push({
            subsystem_id: r.subsystem.id,
            permission_ids: r.permissions.map(x => x.id)
          });
        });

        // save as template
        this.loading = true;
        this.$axios.post("/api/sec_tpl_save", { 
          role_id: this.role_model.id,
          template: template
        })
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'template_saved'){
            this.closeSaveTemplatesModal();
            // REFRESH TEMPLATES
            this.loadTemplates();
            this.$notify({
              title: 'Success',
              message: 'Template Saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.message == 'template_save_error'){
            this.closeSaveTemplatesModal();
            this.$notify({
              title: 'Failed',
              message: 'Save Error. Pleae notify your sysadmin.',
              type: 'warning',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.savetpl.setErrors(err.response.data.errors);
          }
        });
      });
    },

    /* Template application confirmation */
    confirmSecurityTemplateApply(index, item)
    {
      swal.fire({
        title: 'Please Confirm',
        text: 'Existing rules will be replaced',
        showCancelButton: true,
        confirmButtonText: 'Apply Template',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.applySecurityTemplate(index, item);
        }
      });
    },

    /* Apply a selected security template to this role (replacing it's security rules) */
    applySecurityTemplate(index, item)
    {
      if(item){
        // Apply Rules
        this.loading = true;
        this.$axios.post("/api/sec_tpl_apply", 
        {
          id: item.id,
          role_id: this.role_model.id
        })
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'template_applied'){
            this.loading = true;
            this.loadRules()
            .then(resp => {
              this.loading = false;
              this.$notify({
                title: 'Success',
                message: 'Template Applied',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
            });
          }
        });

        this.closeBrowseTemplatesModal();
        this.templateLoaded = true;
        this.loadedTemplateName = item.name;
        this.loadedTemplateId = item.id;
      }
    },

    confirmSecurityTemplateRemoval(index, item)
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
          this.removeSecurityTemplate(index, item);
        }
      });
    },

    removeSecurityTemplate(index, item)
    {
      this.loading = true;
      this.$axios.post("/api/sec_tpl_destroy", {id: item.id})
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'template_removed'){

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
    },

    showBrowseTemplatesModal()
    {
      this.browseTemplatesModalVisible = true;
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

    closeSaveTemplatesModal()
    {
      this.saveTemplatesModalVisible = false;
    }
  },

  mounted()
  {
    this.resetValidation();
  },

  created()
  {
    this.loadRole();
    this.loadRules();
    this.loadTemplates();
  }
};
</script>
<style>
.el-select { display: block; }
.invalid-feedback { position: absolute; }
.el-select .el-select__tags .el-tag .el-tag__close.el-icon-close {
  background-color:rgba(0,0,0,0.25);
}
</style>
