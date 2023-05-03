<template>
  <div class="content">
    <base-header class="pb-6" type="users">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">User Management</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add New User Dialog -->
      <b-modal v-model="showNewUserModal" body-class="add_user_modal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Add New User</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='add_user_form' slim>

            <b-row>
              <b-col md>
                <base-input
                  placeholder="Full Name"
                  name="name"
                  rules="required"
                  vid="name"
                  v-model="model.name">
                </base-input>
              </b-col>
              <b-col md>
                <base-input
                  @blur="runPrechecks"
                  placeholder="Email Address"
                  name="email"
                  rules="required"
                  vid="email"
                  v-model="model.email">
                </base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input placeholder="Mobile" name="mobile" vid="mobile" v-model="model.mobile"></base-input>
              </b-col>
              <b-col md>
                <base-input placeholder="Physical Address" autocomplete="nope" name="address" vid="address" v-model="model.address"></base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input name="u.o.m" rules="required" vid="unit_of_measure">
                  <el-select v-model="model.unit_of_measure" filterable placeholder="Unit of Measure">
                    <el-option label="Metric" :value="1"></el-option>
                    <el-option label="Imperial" :value="2"></el-option>
                  </el-select>
                </base-input>
              </b-col>
              <b-col md>
                <base-input name="timezone" rules="required" vid="timezone" autocomplete="nope">
                  <el-select
                    v-if="timeZones.length"
                    v-model="model.timezone"
                    filterable
                    placeholder="Timezone"
                    autocomplete="nope">
                    <el-option 
                      v-for="item in timeZones"
                      :key="item._key"
                      :label="item._lab"
                      :value="item._key">
                    </el-option>
                  </el-select>
                </base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input name="password" autocomplete="new-password" rules="required|confirmed:password_confirmation" type="password" placeholder="Password" vid="password" v-model="model.password">
                </base-input>
              </b-col>
              <b-col md>
                <base-input name="confirmation" autocomplete="new-password" rules="required" type="password" placeholder="Confirm Password" vid="password_confirmation" v-model="model.password_confirmation">
                </base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input
                  name="cc"
                  rules="required"
                  vid="company"
                  autocomplete="off"
                  v-b-tooltip.hover.top title="The user's owning entity.">
                  <el-select @change="companyChanged" v-model="model.company_id" filterable placeholder="Entity" :disabled="Object.keys(companies).length < 2" autocomplete="off">
                    <el-option v-for="company in companies" :key="company.id" :label="company.company_name" :value="company.id"></el-option>
                  </el-select>
                </base-input>
              </b-col>
              <b-col md>
                <base-input 
                  name="role"
                  rules="required"
                  vid="role"
                  v-b-tooltip.hover.top title="A user's role determines their level of access to various modules."
                  :disabled="!model.company_id || Object.keys(roles).length < 2">
                  <el-select v-if="roles && model.company_id" v-model="model.role_id" filterable placeholder="Role" :disabled="!model.company_id">
                    <el-option v-for="role in roles" :key="role.id" :label="role.role_name" :value="role.id"></el-option>
                  </el-select>
                </base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input
                  name="group"
                  vid="group"
                  placeholder="Group (Optional)"
                  v-b-tooltip.hover.top title="Optionally add this user to a security group."
                  :disabled="!model.company_id || !groups">
                  <el-select
                    v-model="model.group_id"
                    filterable
                    placeholder="Group (Optional)"
                    :disabled="!model.company_id || !groups">
                    <el-option-group v-if="model.company_id && groups" :label="companies['"'+model.company_id.toString()+'"'].company_name + ' User Groups'">
                      <el-option v-for="group in groups" :key="group.id" :label="group.group_name" :value="group.id"></el-option>
                    </el-option-group>
                  </el-select>
                </base-input>
              </b-col>
            </b-row>

            <b-row v-if="model.company_id && (isAdmin() || isDistributor())">
              <b-col md>
                <base-input name="is_distributor" vid="is_distributor">
                  <b-form-checkbox size="lg" v-model="model.is_distributor" unchecked-value="0" value="1">Distributor</b-form-checkbox>
                </base-input>
              </b-col>
            </b-row>

            <b-row v-if="model.company_id && (isAdmin() || isDistributor()) && model.is_distributor == '1'">
              <b-col md>
                <template v-if="dist_list && Object.keys(dist_list).length">
                  <base-input label="Managed Entities" name="managed_companies" vid="managed_companies">
                    <el-select
                    :disabled="!model.is_distributor"
                    v-model="model.managed_company_ids"
                    value-key="id"
                    multiple
                    filterable
                    placeholder="Choose">
                      <el-option v-for="company in dist_list"
                        :key="company.id"
                        :label="company.company_name"
                        :value="company.id">
                      </el-option>
                    </el-select>
                  </base-input>
                </template>
                <template v-else>
                  <b-alert variant="primary" show><strong>{{ companies['"'+model.company_id.toString()+'"'].company_name }}</strong> currently has no manageable subsidiary entities available to pre-assign to this distributor user.</b-alert>
                </template>
              </b-col>
            </b-row>

          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <base-button type="outline-primary" class="ml-auto" @click="closeAddUserModal()">Cancel</base-button>
            <base-button type="primary" @click='addUser()'>Add</base-button>
        </template>
      </b-modal>

      <!-- Users Table -->

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">
        
        <template slot="header">

          <base-button 
            :disabled="!userCan('Add','Users') || loading"
            @click.native="showAddUserModal()"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Add a new user account to an existing entity and role."
            icon>
            Add User
          </base-button>

        </template>
        
        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadUsers">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="entities && entities.length && entities.length > 1"
              class="fullwidth"
              clearable
              filterable
              placeholder="Filter by Entity.."
              v-model="filterEntity"
              @change="loadUsers">
              <el-option v-for="e in entities" :value="e.id" :label="e.company_name" :key="e.id"></el-option>
            </el-select>
          </b-col>
          <b-col md> 
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadUsers"
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

              <template #cell(unit_of_measure)="data">
                {{ data.item.unit_of_measure == 1 ? 'Metric' : 'Imperial' }}
              </template>

              <template #cell()="data">
                {{ data.value }}
              </template>

              <template #cell(is_active)="data" v-if="userCan('Lock', 'Users')">
                <b-icon variant="success" data.value :icon="data.value ? 'check-circle' : 'circle'" aria-hidden="true"></b-icon>
              </template>

              <template #cell(is_admin)="data" v-if="isAdmin()">
                <b-icon variant="success" data.value :icon="data.value ? 'check-circle' : 'circle'" aria-hidden="true"></b-icon>
              </template>

              <template #cell(is_distributor)="data" v-if="isAdmin() || isDistributor()">
                <b-icon variant="success" data.value :icon="data.value ? 'check-circle' : 'circle'" aria-hidden="true"></b-icon>
              </template>

              <template #cell(actions)="data" v-if="canAction">
                  
                <div class="d-flex justify-content-center">
                  <b-button
                    :disabled="!userCan('View','Users', data.item.id, 'O')"
                    @click="handleShow(data.index, data.item)"
                    class="btn"
                    size="sm"
                    variant="outline-primary"
                    icon>
                  Show
                  </b-button>
                  <b-button
                    :disabled="!userCan('Edit','Users', data.item.id, 'O')"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    size="sm"
                    variant="outline-primary"
                    icon>
                  Configure
                  </b-button>
                  <!-- Users cannot delete themselves while logged in -->
                  <b-button
                    :disabled="userCan('Delete','Users', data.item.id, 'O') ? 
                      (($store.state.user_obj && $store.state.user_obj.id != data.item.id) ? false : true ) : true"
                    @click="handleDelete(data.index, data.item)"
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
              <b-pagination @input="loadUsers" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
            </b-col>
          </b-row>
        </div>

      </card>

    </b-container>
  </div>
</template>
<script>
import { Select, Option } from 'element-ui';
import { parse } from 'date-fns';
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option
  },

  data() {
    return {
      
      loading: false,
      bInitialQuery: true,

      tableColumns: [
        {
          key: 'name',
          label: 'Name',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'email',
          label: 'Email',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'role_name',
          label: 'Role',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'unit_of_measure',
          label: 'Unit of Measure',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'timezone',
          label: 'Timezone',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'is_active',
          label: 'Active',
          sortable: true,
          thClass: 'valign halign',
          tdClass: 'valign halign'
        }
      ],

      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: '',
      entities: [],
      sortBy: 'name',
      sortDir: 'asc',

      showNewUserModal: false,

      timeZones: [],
      roles: [],
      
      companies: [],
      dist_list: [],

      subsystems: [],
      groups: [],

      model: {
        name: '',
        email: '',
        mobile: '',
        address: '',
        unit_of_measure: '',
        timezone: '',
        role: '', /* Transitional, remove later on */
        password: '',
        password_confirmation: '',
        is_distributor: 0,
        role_id: null,
        company_id: this.$store.state.user_obj.company_id,
        group_id: null,
        managed_company_ids: []
      },

      canAction: false
    };
  },
  methods: {
    
    loadUsers()
    {
      this.loading = true;

      this.$axios.post("/api/users", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      }).then((resp) => {

        if(this.bInitialQuery){
          this.companyChanged();
        }

        this.loading = false;
        this.bInitialQuery = false;

        if(this.isAdmin() || this.userLimits('View', 'Users', 'C').length > 1){
          this.tableColumns.unshift({
            key: 'company_name',
            label: 'Entity',
            sortable: true,
            tdClass: 'valign'
          });
        }

        if(this.isAdmin()){
          this.tableColumns.push({
            key: 'is_admin',
            label: 'Admin',
            sortable: true,
            thClass: 'valign halign',
            tdClass: 'valign halign'
          });
        }

        if(this.isAdmin() || this.isDistributor()){
          this.tableColumns.push({
            key: 'is_distributor',
            label: 'Distributor',
            sortable: true,
            thClass: 'valign halign',
            tdClass: 'valign halign'
          });
        }

        if(this.isAdmin() || this.userCan('Edit', 'Users') || this.userCan('Delete', 'Users')){
          this.canAction = true;
          this.tableColumns.push({
            key: 'actions',
            label: 'Actions',
            thClass: 'halign'
          });
        }

        this.tableData = resp.data.users;
        this.totalRows = resp.data.total;
        this.entities  = resp.data.entities;

        if(this.totalRows == 0){ this.currentPage = 1; }

      });
    },

    loadCompanies()
    {
      this.$axios.post("/api/companies_list", {
        context: [ { verb: 'Add', module: 'Users' } ]
      }).then((resp) => {
        this.companies = resp.data.companies;
      });
    },

    loadDistributors()
    {
      this.$axios.post('/api/companies_dist_list', {
        /* New user's company_id (Defaults to current Admin/Distributor's company_id (SAME)) */
        company_id: this.model.company_id
      }).then((resp) => {
        if(resp.data){
          this.dist_list = resp.data.companies;
        }
      });
    },

    loadRoles()
    {
      this.$axios.post("/api/roles_by_cc", {
        company_id: this.model.company_id ? this.model.company_id : this.$store.state.user_obj.company_id
      }).then((response) => {
          this.roles = response.data.roles;
      });
    },

    loadGroups()
    {
      this.$axios.post("/api/groups_list", {
        company_id: this.model.company_id ? this.model.company_id : this.$store.state.user_obj.company_id,
        context: [ { verb: 'Add', module: 'Users' } ]
      })
      .then((resp) => {
        this.groups = resp.data.groups;
        this.subsystems = resp.data.subsystems;
      });
    },

    companyChanged()
    {
      this.model.role_id = null;
      this.model.group_id = null;
      this.loadRoles();
      this.loadGroups();
      if(this.isAdmin() || this.isDistributor()){
        this.loadDistributors();
      }
    },

    loadTimezones()
    {
      this.$axios.get("/api/getTimezones")
      .then((response) => {
        if(response.data){
          let _timezones = JSON.parse(JSON.stringify(response.data.timezones));
          let _offsets = JSON.parse(JSON.stringify(response.data.offsets));
          if(_timezones && _timezones.length){
            for(var i = 0; i < _timezones.length; i++){
              this.timeZones.push({
                _key: i.toString(), 
                _lab: _timezones[i] + ' (' + _offsets[_timezones[i]] + ')'
              });
            }
          }
        }
      });
    },

    handleShow(index, row)
    {
      this.$router.push('/users_manage/show/' + row.email)
    },

    handleEdit(index, row)
    {
      this.$router.push('/users_manage/edit/' + row.email)
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Are you sure?',
        text: `Please confirm user removal`,
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
          this.deleteUser(row);
        }
      });
    },

    deleteUser(row)
    {
      this.loading = true;
      this.$axios.post("/api/userdestroy", { id: row.id })
      .then((response) => {
        this.loading = false;

        if(response.data.message == 'user_removed'){
          let indexToDelete = this.tableData.findIndex(
            tableRow => tableRow.id === row.id
          );
          if (indexToDelete >= 0) {
            this.tableData.splice(indexToDelete, 1);
          }
          this.$notify({
            title: 'Status',
            message: 'User Deleted',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    addUser()
    {
      this.$refs.add_user_form.validate().then(success => {
        if (!success) { return; }
        this.loading = true;
        this.$axios.post("/api/usernew", this.model)
        .then((response) => {
          this.loading = false;
          if(response.data.message == 'user_added'){
            this.loadUsers();
            this.closeAddUserModal();
            this.$notify({
              title: 'Success',
              message: 'New user added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.add_user_form.setErrors(err.response.data.errors);
          }
        });
      });
    },

    runPrechecks(e)
    {
      if(this.model.email && this.model.email != ''){
        let email_address = e.target.value;
        this.$axios.get("/api/userexists/" + email_address)
        .then((resp) => {
          if(resp.data && resp.data.user_exists){
            this.model.email = '';
            this.$notify({
              title: 'Error',
              message: 'User (' + email_address + ') already exists',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        });
      }
    },

    showAddUserModal()
    {
      if(this.isAdmin() || this.isDistributor()){
        this.loadDistributors();
      }
      this.showNewUserModal = true;
    },

    closeAddUserModal()
    {
      this.clearModel();
      this.showNewUserModal = false;
    },

    clearModel()
    {
      let _keys = Object.keys(this.model);
      for(var i = 0; i < _keys.length; i++){
        this.model[_keys[i]] = '';
      }
      this.model.company_id = this.$store.state.user_obj.company_id;
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadUsers();
    }
  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadTimezones();
    this.loadUsers();
    this.loadCompanies();
  }
};
</script>
<style>
  .no-border-card .card-footer{
    border-top: 0;
  }

  .add_user_modal p {
    font-size:0.9rem;
  }
</style>