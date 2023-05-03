<template>
  <div class="content">
    <base-header class="pb-6" type="activitylog">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Activity Logs</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2" v-if="isAdmin()">
        
        <template slot="header">
          Log Entries
        </template>
        
        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadActivityLogs">
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
              placeholder="Filter by Entity.."
              v-model="filterEntity"
              @change="loadActivityLogs">
              <el-option v-for="e in entities" :value="e.company_name" :label="e.company_name" :key="e.id"></el-option>
            </el-select>
          </b-col>
          <b-col md> 
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadActivityLogs"
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
              stacked="md"
              responsive
              show-empty
              primary-key="id"
              no-local-sorting
              @sort-changed="sortingChanged"
              :fields="tableColumns"
              :items="tableData"
              :busy.sync="bInitialQuery">

              <template #cell()="data">
                <span class='tblcell'>{{ data.value }}</span>
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
              <b-pagination @input="loadActivityLogs" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
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
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Steps.name]:  Steps,
    [Step.name]:   Step,
    [Select.name]: Select,
    [Option.name]: Option
  },
  data() {
    return {

      loading: false,
      bInitialQuery: true,

      tableColumns: [
        {
          key: 'company',
          label: 'Entity',
          tdClass: 'valign',
          sortable: true
        },
        {
          key: 'user',
          label: 'User',
          tdClass: 'valign',
          sortable: true
        },
        {
          key: 'subsystem',
          label: 'Subsystem',
          tdClass: 'valign',
          sortable: true
        },
        {
          key: 'operation',
          label: 'Operation',
          tdClass: 'valign',
          sortable: true
        },
        {
          key: 'details',
          label: 'Details',
          tdClass: 'valign',
          sortable: true
        },
        {
          key: 'occurred',
          label: 'Occurred (UTC)',
          tdClass: 'valign',
          sortable: true
        }
      ],

      tableData: [],
      entities: [],

      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: '',
      sortBy: 'occurred',
      sortDir: 'desc',

      refreshTimer: null
    };
  },
  methods: {

    loadActivityLogs()
    {
      this.loading = true;

      this.$axios.post("/api/activity_logs", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      }).then((resp) => {

        this.loading = false;
        
        this.tableData = resp.data.rows;
        this.totalRows = resp.data.total;
        this.entities  = resp.data.entities;

        if(this.totalRows == 0){ this.currentPage = 1; }

        if(this.bInitialQuery){
          this.bInitialQuery = false;
          this.refreshTimer = setInterval(() => { this.loadActivityLogs(); }, 10000);
        }
      });
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadActivityLogs();
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
    this.loadActivityLogs();
  },

  beforeRouteLeave (to, from, next)
  {
    if(this.refreshTimer){
      clearInterval(this.refreshTimer);
    }
    next();
  }
};
</script>
<style>
  .tblcell { max-width:100%; }
</style>