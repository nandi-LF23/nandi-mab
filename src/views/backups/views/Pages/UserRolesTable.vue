<template>
  <div class="content">
    <base-header class="pb-6" type="roles">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Roles & Security</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <b-modal v-model="showNewRoleModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Create New Role</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='form' slim>
            <form role="form" autocomplete="off" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <base-input 
                    name="role name"
                    rules="required"
                    placeholder="Role Name"
                    vid="name"
                    v-b-tooltip.hover.top title="Enter a descriptive name for the role."
                    v-model="model.role_name">
                    </base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col md>
                  <base-input name="company" rules="required" vid="company" v-b-tooltip.hover.top title="The entity that the role would belong to.">
                    <el-select v-model="model.company_id" filterable placeholder="Entity" :disabled="!multipleCompanies">
                      <el-option v-for="company in companies" :key="company.id" :label="company.company_name" :value="company.id"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <base-button type="outline-primary" class="ml-auto" @click="closeCreateRoleModal()">Cancel</base-button>
            <base-button type="primary" @click='createRole()'>Create</base-button>
        </template>
        
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">
        <template slot="header">
          <base-button
            :disabled="!userCan('Add', 'Roles') || loading"
            @click.native="showcreateRoleModal()"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Add a new entity role. Roles group users into common security groups."
            icon>
            Add Role
          </base-button>

          <base-button
            :disabled="!userCan('View', 'Groups') || loading"
            @click.native="goToGroups()"
            class="btn"
            type="primary"
            size="sm"
            v-b-tooltip.hover.top title="Manage groups. Groups are used to effect permissions on a group scale."
            icon>
            Manage Groups
          </base-button>
        </template>
        
        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadRoles">
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
              @change="loadRoles">
              <el-option v-for="e in entities" :value="e.id" :label="e.company_name" :key="e.id"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadRoles"
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

              <template #cell(actions)="data" v-if="canAction">
                  
                <div class="d-flex justify-content-center">
                  <b-button
                    :disabled="!userCan('Edit', 'Roles', data.item.id, 'O')"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    size="sm"
                    variant="outline-primary"
                    icon>
                  Configure
                  </b-button>
                  <b-button
                    :disabled="!userCan('Delete', 'Roles', data.item.id, 'O')"
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
              <b-pagination @input="loadRoles" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
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
          key: 'role_name',
          label: 'Role',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'user_count',
          label: 'User Count',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'rule_count',
          label: 'Rule Count',
          sortable: true,
          tdClass: 'valign'
        }
      ],
      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: '',

      entities: [],
      sortBy: this.isAdmin() ? 'company_name' : 'role_name',
      sortDir: 'asc',

      companies: [],
      showNewRoleModal: false,
      model: {
        role_name: '',
        company_id: ''
      },
      multipleCompanies: false,
      canAction: false
    };
  },
  methods: {
    
    loadRoles()
    {
      this.loading = true;

      this.$axios.post("/api/roles", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      })
        .then((resp) => {
          
          this.loading = false;
          this.bInitialQuery = false;

          if(this.isAdmin() || this.userLimits('View', 'Roles', 'C').length > 1){
            this.multipleCompanies = true;
            this.tableColumns.unshift({
              key: 'company_name',
              label: 'Entity',
              sortable: true,
              tdClass: 'valign'
            });
          }

          if(this.isAdmin() || this.userCan('Edit', 'Roles') || this.userCan('Delete', 'Roles')){
            this.canAction = true;
            this.tableColumns.push({
              key: 'actions',
              label: 'Actions',
              thClass: 'halign'
            });
          }

          this.tableData = resp.data.rows;
          this.totalRows = resp.data.total;
          this.entities = resp.data.entities;

          if(this.totalRows == 0){ this.currentPage = 1; }
      });
    },

    loadCompanies()
    {
      this.loading = true;
      this.$axios.post("/api/companies_list", {
        context: [ { verb: 'Add', module: 'Roles' } ]
      }).then((resp) => {
        this.loading = false;
        this.companies = resp.data.companies;
      }); 
    },

    handleEdit(index, row)
    {
      this.$router.push('/roles_manage/edit/' + row.id)
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Are you sure?',
        text: `Please confirm role removal`,
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
          this.deleteRole(row);
        }
      });
    },

    deleteRole(row)
    {
      this.loading = true;
      this.$axios.post("/api/role_destroy", { id: row.id })
      .then((response) => {
        this.loading = false;

        if(response.data.message == 'role_removed'){

          let indexToDelete = this.tableData.findIndex(
            tableRow => tableRow.id === row.id
          );
          if (indexToDelete >= 0) {
            this.tableData.splice(indexToDelete, 1);
          }

          this.$notify({
            title: 'Success',
            message: 'Role Removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });

        } else if(response.data.message == 'role_in_use'){
          this.$notify({
            title: 'Failure',
            message: 'Cannot remove role: ' + response.data.object_count + ' ' + response.data.object_type + ' still using it.',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    createRole()
    {
      this.$refs.form.validate().then(success => {
        if (!success) { return; }
        this.loading = true;
        this.$axios.post("/api/role_add", this.model)
        .then((response) => {
          this.loading = false;
          if(response.data.message == 'role_added'){
            this.tableData.unshift(response.data.role);
            this.closeCreateRoleModal();
            this.$notify({
              title: 'Success',
              message: 'New Role Created',
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

    showcreateRoleModal()
    {
      this.showNewRoleModal = true;
    },

    closeCreateRoleModal()
    {
      this.clearModel();
      this.showNewRoleModal = false;
    },

    clearModel()
    {
      let _keys = Object.keys(this.model);
      for(var i = 0; i < _keys.length; i++){
        this.model[_keys[i]] = '';
      }
    },

    goToGroups()
    {
      this.$router.push({
        name: "groups_manage",
        params: {}
      });
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadRoles();
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadRoles();
    this.loadCompanies();
  }
};
</script>
<style>
  .no-border-card .card-footer{
    border-top: 0;
  }
</style>