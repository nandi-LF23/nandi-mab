<template>
  <div class="content">
    <base-header class="pb-6" type="soilmoisture">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Soil Moisture</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadHardware">
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
              @change="loadHardware">
              <el-option v-for="e in entities" :value="e.id" :label="e.company_name" :key="e.id"></el-option>
            </el-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText"
                :debounce="1000"
                @update="loadHardware"
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

              <template #cell(field_name)="data">
                <strong>
                  <router-link v-if="userCan('Graph', 'Soil Moisture', data.item.id, 'O')"
                    :to="{ 
                      name: 'soil_moisture_graph',
                      params: { node_address: data.item.node_address }
                    }">
                    {{ data.value }}
                  </router-link>
                  <template v-else>
                    {{ data.value }}
                  </template>
                </strong>
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
                    :disabled="!userCan('Graph', 'Soil Moisture', data.item.id, 'O')"
                    @click="handleGraph(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm"
                    icon>
                    Graph
                    </b-button>
                    <b-button
                    :disabled="!userCan('Edit', 'Soil Moisture', data.item.id, 'O')"
                    @click="handleEdit(data.index, data.item)"
                    class="btn"
                    variant="outline-primary"
                    size="sm"
                    icon>
                    Manage
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
              <b-pagination @input="loadHardware" v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"></b-pagination>
            </b-col>
          </b-row>
        </div>
 
      </card>

    </b-container>
  </div>
</template>
<script>

import { parse } from 'date-fns';
import mab_utils from '../../util/mab-utils';
import { Select, Option } from 'element-ui';

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
          key: 'field_name',
          label: 'Field Name',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'node_address',
          label: 'Node Address',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'commissioning_date',
          label: 'Installed',
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
        }
      ],
      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      filterEntity: null,
      entities: [],
      sortBy: this.isAdmin() ? 'node_address' : 'date_time',
      sortDir: 'desc',

      canAction: false
    };
  },
  methods: {
    loadHardware()
    {
      this.loading = true;

      this.$axios.post("/api/SMTable", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial:  this.bInitialQuery,
        filter:   this.filterText,
        entity:   this.filterEntity,
        sort_by:  this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      }).then(resp => {

        this.loading = false;
        this.bInitialQuery = false;

        if(this.isAdmin() || this.userLimits('View', 'Soil Moisture', 'C').length > 1){
          this.tableColumns.unshift({
            key: 'company_name',
            label: 'Entity',
            sortable: true,
            tdClass: 'valign'
          });
        }

        if(this.userCan('Graph', 'Soil Moisture') || this.userCan('Edit', 'Soil Moisture')){
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
    
    handleGraph(index, row) {
      // using named routes in order to pass multiple args
      this.$router.push({ 
        name: "soil_moisture_graph",
        params:{ node_address: row.node_address }
      });
    },

    handleEdit(index, row) {
      this.$router.push({
        name: "soil_moisture_edit",
        params: { node_address: row.node_address }
      });
    },

    sortingChanged(e)
    {
      this.sortBy  = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadHardware();
    }

  },

  // filter change reset page
  watch: { filterText: function(n,o) { this.currentPage = n != o ? 1 : this.currentPage } },

  mounted()
  {
      this.loadHardware();
  }
};
</script>
<style>
  .no-border-card .card-footer{
    border-top: 0;
  }
</style>
