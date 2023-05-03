<template>
  <div class="content">
    <base-header class="pb-6" type="dataformats">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Data Formats</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <!-- Add New Data Format -->
      <b-modal size="xl" v-model="showNewDFModal" centered no-close-on-esc no-close-on-backdrop>

        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Create New Data Format</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='addform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input
                    vid="name"
                    rules="required|max:128"
                    name="format name"
                    placeholder="Data Format Name"
                    v-model="add_model.name">
                  </base-input>
                </b-col>
                <b-col>
                  <base-input name="format" rules="required" vid="format">
                    <el-select
                      @change="initSpec(add_model)"
                      v-model="add_model.format"
                      filterable
                      placeholder="Data Format Type"
                      :disabled="!isAdmin() && format_types.length < 2">
                      <el-option v-for="format in format_types" :key="format" :label="format" :value="format"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
              <template v-if="add_model.format == 'JSON'">
                <b-row>
                  <b-col>
                    <base-input>
                      <el-select
                        class="fullwidth"
                        clearable
                        multiple
                        placeholder="Supported Types.."
                        v-model="add_model.spec.meta.supported_types">
                        <el-option v-for="type in node_types" :label="type" :key="type" :value="type"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input>
                      <el-select
                        class="fullwidth"
                        clearable
                        placeholder="Mode.."
                        v-model="add_model.spec.meta.mode">
                        <el-option v-for="mode in spec_modes" :label="mode" :key="mode" :value="mode"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
                <template v-if="add_model.spec.meta.mode && add_model.spec.meta.supported_types">
                  <b-row>
                      <b-col><h4>JSON Format Designer</h4></b-col>
                  </b-row>
                  <b-row>
                    <b-col>
                      <JSONFieldMapper
                        v-if="add_model.spec.meta.mode && add_model.spec.meta.supported_types"
                        :meta="add_model.spec.meta"
                        :fields="add_model.spec.data"
                        :targets="targets">
                      </JSONFieldMapper>
                    </b-col>
                  </b-row>
                </template>
              </template>
              <template v-else-if="add_model.format == 'Binary'">
                <b-row>
                  <b-col>
                    <h4>Binary Format Designer</h4>
                  </b-col>
                </b-row>
              </template>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeAddDFModal">Cancel</b-button>
            <b-button variant="primary" @click="addDataFormat">Create</b-button>
        </template>
      </b-modal>

      <!-- Edit Existing Data Format -->
      <b-modal size="xl" v-model="showEditDFModal" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Edit Data Format</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='editform' slim>
            <form role="form" @submit.prevent="()=>false">
              <b-row>
                <b-col>
                  <base-input
                    vid="name"
                    rules="required|max:128"
                    name="format name"
                    placeholder="Data Format Name"
                    v-model="edit_model.name">
                  </base-input>
                </b-col>
                <b-col>
                  <base-input name="format" rules="required" vid="format">
                    <el-select
                      @change="initSpec(edit_model)"
                      v-model="edit_model.format"
                      filterable
                      placeholder="Data Format Type"
                      :disabled="!isAdmin() && format_types.length < 2">
                      <el-option v-for="format in format_types" :key="format" :label="format" :value="format"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
              </b-row>
              <template v-if="edit_model.format == 'JSON'">
                <b-row>
                  <b-col>
                    <base-input label="Supported Types">
                      <el-select
                        class="fullwidth"
                        clearable
                        multiple
                        placeholder="Supported Types.."
                        v-model="edit_model.spec.meta.supported_types">
                        <el-option v-for="type in node_types" :value="type" :label="type" :key="type"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col>
                    <base-input label="Mode">
                      <el-select
                        class="fullwidth"
                        clearable
                        placeholder="Mode.."
                        v-model="edit_model.spec.meta.mode">
                        <el-option v-for="mode in spec_modes" :value="mode" :label="mode" :key="mode"></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                </b-row>
                <template v-if="edit_model.spec.meta.mode && edit_model.spec.meta.supported_types">
                  <b-row>
                      <b-col><h4>JSON Format Designer</h4></b-col>
                  </b-row>
                  <b-row>
                    <b-col>
                      <JSONFieldMapper
                        :meta="edit_model.spec.meta"
                        :fields="edit_model.spec.data"
                        :targets="targets">
                      </JSONFieldMapper>
                    </b-col>
                  </b-row>
                </template>
              </template>
              <template v-else-if="edit_model.format == 'Binary'">
                <b-row>
                  <b-col>
                    <h4>Binary Format Designer</h4>
                  </b-col>
                </b-row>
              </template>
            </form>
          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
            <b-button variant="outline-primary" class="ml-auto" @click="closeEditDFModal">Cancel</b-button>
            <b-button variant="primary" @click='updateDataFormat'>Save</b-button>
        </template>
      </b-modal>

      <card class="no-border-card" body-classes="px-0 pb-0" footer-classes="pb-2">
        <template slot="header">

          <base-button 
            :disabled="!isAdmin() || loading"
            @click="openAddDFModal"
            class="btn"
            size="sm"
            type="primary"
            v-b-tooltip.hover.top title="Data Formats are used to recognize and parse data packets."
            icon>
            Add Data Format
          </base-button>

        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadDataFormats">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <el-select v-if="format_types && format_types.length > 1"
              class="fullwidth"
              clearable
              filterable
              placeholder="Filter by Type.."
              v-model="filterDataFormats"
              @change="loadDataFormats">
              <el-option v-for="type in format_types" :value="type" :label="type" :key="type"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input 
                v-model="filterText"
                :debounce="1000"
                @update="loadDataFormats"
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
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm">
                    Edit
                  </b-button>

                  <b-button :disabled="!isAdmin()"
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
              <b-pagination @input="loadDataFormats" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
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
import JSONFieldMapper from '../../custom_components/JSONFieldMapper';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    [OptionGroup.name]: OptionGroup,
    JSONFieldMapper
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
          key: 'format',
          label: 'Format',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'actions',
          label: 'Actions',
          thClass: 'halign'
        }
      ],

      format_types: [ 'JSON', 'Binary' ],
      node_types: [ 'Nutrients', 'Soil Moisture', 'Wells', 'Water Meter' ],
      spec_modes: [ 'Single', 'Multiple' ],
      tableData: [],

      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterDataFormats: '',

      sortBy: 'name',
      sortDir: 'asc',

      showNewDFModal: false,
      showEditDFModal: false,

      add_model: {
        id: null,
        name: null,
        format: null,
        spec: {
          meta: {},
          data: []
        }
      },

      edit_model: {
        id: null,
        name: null,
        format: null,
        spec: {
          meta: {},
          data: []
        }
      },

      targets: []

    }
  },

  mounted()
  {
    this.loadDataFormats();
  },

  methods: {

    initSpec(model)
    {
      this.initMeta(model);
      this.initData(model);
    },

    initMeta(model){

      // Common
      let meta = {
        mode: '', // single/multi
        supported_types: []
      }

      // JSON Specific Props
      if(model.format == 'JSON'){
        
        // meta.xxx = '';

      // Binary Specific Props
      } else if(model.format == 'Binary'){

        // meta.xxx = '';
        
      }

      model.spec.meta = meta;
    },

    initData(model){

      // JSON Specific Data
      if(model.format == 'JSON'){

        model.spec.data = [{
          source:   null,
          type:     null,
          target:   null,
          config:   null,
          logic:    [],
          children: []
        }];

      // Binary Specific Data
      } else if(model.format == 'Binary'){

        model.spec.data = [{}];

      }
    },

    resetModel(model)
    {
      model.id = null;
      model.name = null;
      this.resetMeta(model);
      this.resetData(model);
      model.format = null;
    },

    resetMeta(model){
      // Common

      // Plurality
      model.spec.meta.mode = '';
      // Supported Types
      if(model.spec.meta.supported_types.length){
        while(model.spec.meta.supported_types.length){
          model.spec.meta.supported_types.pop();
        }
      }
      // JSON Reset
      if(model.format == 'JSON'){

      // Binary Reset
      } else if(model.format == 'Binary'){

      }
    },

    resetData(model){

      if(model.spec.data.length){
        while(model.spec.data.length){
          model.spec.data.pop();
        }
      }

      if(model.format == 'JSON'){

        model.spec.data.push({
          source: null,
          type: null,
          target: null,
          config: null,
          logic: [],
          children: []
        });

      } else if(model.format == 'Binary'){

        // TODO
        model.spec.data.push({

        });

      }
    },

    loadDataFormats()
    {
      this.loading = true;

      return this.$axios.post("/api/dataformats", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        format:   this.filterDataFormats,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'asc' : 'desc'
      })
      .then(resp => {

        this.loading = false;
        this.bInitialQuery = false;

        this.tableData = resp.data.formats;
        this.totalRows = resp.data.total;
        this.targets   = resp.data.targets;

        if(this.totalRows == 0){ this.currentPage = 1; }

        return resp;

      });
    },

    handleEdit(index, row)
    {
      this.edit_model = JSON.parse(JSON.stringify(row)); /* deep copy */
      this.edit_model.spec = JSON.parse(this.edit_model.spec);
      this.openEditDFModal();
    },

    handleDelete(index, row)
    {
      swal.fire({
        title: 'Data Format Deletion',
        text: `Please confirm data format removal`,
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
          this.deleteDataFormat(row);
        }
      });
    },

    addDataFormat()
    {
      this.$refs.addform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;

        this.$axios.post("/api/dataformats_add", this.add_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'df_created'){
            
            this.closeAddDFModal();
            this.loadDataFormats();

            this.$notify({
              title: 'Success',
              message: 'New data format added',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          console.log(err);
          if(err.response.data.errors){
            this.$refs.addform.setErrors(err.response.data.errors);
          }
        });
      });
    },

    updateDataFormat()
    {
      this.$refs.editform.validate().then(success => {
        if (!success) { return; }

        this.loading = true;

        // DEBUGGING / CACHE BUSTING
        this.edit_model.spec.meta.random = Math.random();
        
        this.$axios.post("/api/dataformats_update", this.edit_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'df_updated'){

            this.closeEditDFModal();
            this.loadDataFormats();

            this.$notify({
              title: 'Success',
              message: 'Data Format updated',
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

    deleteDataFormat(row)
    {
      this.loading = true;
      this.$axios.post("/api/dataformats_destroy", { id: row.id })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'df_removed'){

          this.loadDataFormats();
          
          this.$notify({
            title: 'Removed',
            message: 'Data Format was removed',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    openAddDFModal()
    {
      this.showNewDFModal = true;
    },

    openEditDFModal()
    {
      this.showEditDFModal = true;
    },
    
    closeAddDFModal()
    {
      this.resetModel(this.add_model);
      this.showNewDFModal = false;
    },

    closeEditDFModal()
    {
      this.resetModel(this.edit_model);
      this.showEditDFModal = false;
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadDataFormats();
    },

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

};
</script>
<style>
</style>
