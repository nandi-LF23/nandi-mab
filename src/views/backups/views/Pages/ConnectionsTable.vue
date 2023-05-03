<template>
  <div class="content">
    <base-header class="pb-6" type="connections">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Connections</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add New Connection -->
      <b-modal v-model="showNewConnModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Create New Connection</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='addform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input vid="name" rules="required|max:128" name="connection name" placeholder="Connection Name" v-model="add_model.name"></base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <base-input name="type" rules="required" vid="company">
                    <el-select
                      @change="initConfig(add_model)"
                      v-model="add_model.type"
                      filterable
                      placeholder="Connection Type"
                      :disabled="!isAdmin() && types.length < 2">
                      <el-option v-for="type in types" :key="type" :label="type" :value="type"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <base-input vid="format" rules="required" name="format">
                    <el-select
                      v-model="add_model.dataformat_id"
                      filterable
                      placeholder="Data Format">
                      <el-option v-for="format in formats" :key="format.id" :label="format.name" :value="format.id"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
              <b-row>

              </b-row>
              <template v-if="add_model.type == 'MQTT'">
                <b-row>
                  <b-col><base-input vid="server" rules="required" name="server" placeholder="Server" v-model="add_model.config.server"></base-input></b-col>
                  <b-col><base-input vid="port" rules="required" name="port" placeholder="Port" v-model="add_model.config.port"></base-input></b-col>
                </b-row>
                <b-row>
                  <b-col><base-input vid="username" rules="required" name="username" placeholder="Username" v-model="add_model.config.username"></base-input></b-col>
                  <b-col><base-input vid="password" rules="required" name="password" placeholder="Password" v-model="add_model.config.password"></base-input></b-col>
                </b-row>
                <b-row>
                  <b-col><base-input vid="topic" rules="required" name="topic" placeholder="Topic" v-model="add_model.config.topic"></base-input></b-col>
                </b-row>
              </template>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeAddConnModal">Cancel</b-button>
            <b-button variant="primary" @click='addConnection'>Create</b-button>
        </template>
      </b-modal>

      <!-- Edit Existing Connection -->
      <b-modal v-model="showEditConnModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Edit Connection</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='editform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input
                    label="Name"
                    v-model="edit_model.name"
                    vid="name"
                    rules="required|max:128"
                    name="connection name"
                    placeholder="Connection Name">
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col>
                  <base-input vid="role" name="type" rules="required">
                    <el-select
                      @change="initConfig(edit_model)"
                      v-model="edit_model.type"
                      filterable
                      placeholder="Type"
                      :disabled="edit_model.status == 'Connected'">
                      <el-option v-for="type in types" :key="type" :label="type" :value="type"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col>
                  <base-input vid="format" rules="required" name="format">
                    <el-select
                      v-model="edit_model.dataformat_id"
                      filterable
                      placeholder="Data Format">
                      <el-option v-for="format in formats" :key="format.id" :label="format.name" :value="format.id"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>

              <template v-if="edit_model.type == 'MQTT'">
                <b-row>
                  <b-col><base-input vid="server" rules="required" name="server" placeholder="Server" v-model="edit_model.config.server"></base-input></b-col>
                  <b-col><base-input vid="port" rules="required" name="port" placeholder="Port" v-model="edit_model.config.port"></base-input></b-col>
                </b-row>
                <b-row>
                  <b-col><base-input vid="username" rules="required" name="username" placeholder="Username" v-model="edit_model.config.username"></base-input></b-col>
                  <b-col><base-input vid="password" rules="required" name="password" placeholder="Password" v-model="edit_model.config.password"></base-input></b-col>
                </b-row>
                <b-row>
                  <b-col><base-input vid="topic" rules="required" name="topic" placeholder="Topic" v-model="edit_model.config.topic"></base-input></b-col>
                </b-row>
              </template>

            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeEditConnModal">Cancel</b-button>
            <b-button variant="primary" @click='updateConnection'>Save</b-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-0" footer-classes="pb-2">
        <template slot="header">

          <base-button 
            :disabled="!isAdmin() || loading"
            @click="openAddConnModal"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Connections are used for configuring incoming data streams from various data sources."
            icon>
            Add Connection
          </base-button>

          <base-button
            :disabled="!isAdmin() || loading"
            @click.native="goToFormats()"
            class="btn"
            type="primary"
            size="sm"
            v-b-tooltip.hover.top title="Navigate to the Data Formats."
            icon>
            Manage Formats
          </base-button>

        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadConnections">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="types && types.length > 1"
              class="fullwidth"
              clearable
              filterable
              placeholder="Filter by Type.."
              v-model="filterConnections"
              @change="loadConnections">
              <el-option v-for="type in types" :value="type" :label="type" :key="type"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input 
                v-model="filterText"
                :debounce="1000"
                @update="loadConnections"
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

              <template #cell(actions)="data">
                <div class="d-flex justify-content-center">
                  <b-button :disabled="!isAdmin()"
                    @click="toggleConnection(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm">
                  {{ data.item.status == 'Disconnected' ? 'Connect' : 'Disconnect' }}
                  </b-button>

                  <b-button :disabled="!isAdmin() || data.item.status.indexOf('Connected') !== -1"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm">
                  Edit
                  </b-button>

                  <b-button :disabled="!isAdmin() || data.item.status.indexOf('Connected') !== -1"
                    @click="handleDelete(data.index, data.item)"
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
              <b-pagination @input="loadConnections" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
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
          key: 'name',
          label: 'Name',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'type',
          label: 'Type',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'status',
          label: 'Status',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'format_name',
          label: 'Data Format',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'actions',
          label: 'Actions',
          thClass: 'halign'
        }
      ],

      types: ['MQTT'],
      tableData: [],

      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterConnections: '',

      sortBy: 'name',
      sortDir: 'asc',

      showNewConnModal: false,
      showEditConnModal: false,

      add_model: {
        id: null,
        name: '',
        type: '',
        status: '',
        dataformat_id: null,
        config: {}
      },

      edit_model: {
        id: null,
        name: '',
        type: '',
        status: '',
        dataformat_id: null,
        config: {},
      },

      refreshTimer: null,

      formats: []

    }
  },
  methods: {
    
    loadConnections()
    {
      this.loading = true;

      return this.$axios.post("/api/connections", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        type:     this.filterConnections,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'asc' : 'desc'
      })
      .then(resp => {

        this.loading = false;
        this.bInitialQuery = false;

        this.tableData  = resp.data.conn_data;
        this.totalRows  = resp.data.total;

        if(this.totalRows == 0){ this.currentPage = 1; }

        this.loadFormats();

        return resp;

      });
    },

    toggleConnection(index, row)
    {
      // Connect
      if(row.status.indexOf('Disconnected') !== -1){
        this.loading = true;
        this.$axios.post("/api/connections_connect", { id: row.id })
        .then(resp => {
          this.loading = false;
          if(resp.data.message == 'initiated'){

            // Ensure timer is cleared initially
            clearInterval(this.refreshTimer);
            this.refresTimer = null;

            this.loadConnections();
            this.$notify({
              title: 'Success',
              message: 'Connection initiated',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });

            let attempt = 0;
            this.refreshTimer = setInterval(() => {
              this.loadConnections().then(() => {
                Object.keys(this.tableData).forEach(conn => {
                  if(
                    this.tableData[conn].name == row.name && 
                    this.tableData[conn].status.indexOf('Connected') !== -1
                  ){
                    clearInterval(this.refreshTimer);
                    this.refresTimer = null;
                    this.$notify({
                      title: 'Success',
                      message: 'Connected successfully',
                      type: 'success',
                      verticalAlign: 'top',
                      horizontalAlign: 'right'
                    });
                  }
                });
              });
              if(attempt == 5){
                clearInterval(this.refreshTimer);
                this.refresTimer = null;
                this.$notify({
                  title: 'Failure',
                  message: 'Could not connect',
                  type: 'warning',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });
              }
              attempt += 1;
            }, 5000);

          }
        });
      // Disconnect
      } else if (row.status.indexOf('Connected') !== -1){
        swal.fire({
          title: 'Disconnect',
          text: `Please confirm disconnection`,
          type: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Disconnect',
          buttonsStyling: false,
          customClass: {
            cancelButton: 'btn btn-outline-primary',
            confirmButton: 'btn btn-primary'
          }
        }).then(result => {

          if (!result.value) { return; }

          this.loading = true;
          this.$axios.post("/api/connections_disconnect", { id: row.id })
          .then(resp => {
            this.loading = false;
            if(resp.data.message == 'initiated'){
              this.loadConnections();
              this.$notify({
                title: 'Success',
                message: 'Disconnected',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
            }
          });
        });
      }
    },

    handleEdit(index, row)
    {
      this.edit_model = JSON.parse(JSON.stringify(row)); /* deep copy */
      this.edit_model.config = JSON.parse(this.edit_model.config);
      this.openEditConnModal();
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Connection Deletion',
        text: `Please confirm connection removal`,
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
          this.deleteConnection(row);
        }
      });
    },

    addConnection()
    {
      this.$refs.addform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        this.$axios.post("/api/connections_add", this.add_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'conn_created'){
            
            this.closeAddConnModal();
            this.loadConnections();

            this.$notify({
              title: 'Success',
              message: 'New connection added',
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

    updateConnection()
    {
      this.$refs.editform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;
        
        this.$axios.post("/api/connections_update", this.edit_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'conn_updated'){

            this.closeEditConnModal();
            this.loadConnections();

            this.$notify({
              title: 'Success',
              message: 'Connection updated',
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

    deleteConnection(row)
    {
      this.loading = true;
      this.$axios.post("/api/connections_destroy", { id: row.id })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'conn_removed'){

          this.loadConnections();
          
          this.$notify({
            title: 'Removed',
            message: 'Connection was removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    loadFormats()
    {
      return this.$axios.get("/api/dataformats_list")
      .then(resp => {
        this.formats = resp.data.formats;
        return resp;
      });
    },

    openAddConnModal()
    {
      this.showNewConnModal = true;
    },

    closeAddConnModal()
    {
      this.resetAddModel();
      this.showNewConnModal = false;
    },

    openEditConnModal()
    {
      this.showEditConnModal = true;
    },

    closeEditConnModal()
    {
      this.showEditConnModal = false;
    },

    resetAddModel()
    {
      this.add_model.id = null;
      this.add_model.name = '';
      this.add_model.type = '';
      this.add_model.status = '';
      this.add_model.dataformat_id = null;
      this.add_model.config = {};
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadConnections();
    },

    initConfig(model)
    {
      if(model.type == 'MQTT'){
        model.config = {
          server: '',
          port: '',
          username: '',
          password: '',
          topic: ''
        };
      }
    },

    goToFormats()
    {
      this.$router.push({
        name: "dataformats",
        params: {}
      });
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadConnections();
  },
};
</script>
<style>
</style>
