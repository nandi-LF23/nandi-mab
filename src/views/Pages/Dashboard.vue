<template>
  <div class="content">
    <base-header class="pb-6" type="dashboard">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">Dashboard</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>
    <b-container fluid class="mt--6">

      <card class="no-border-card" body-classes="px-0 pb-1" footer-classes="pb-2">

        <template slot="header">
          <b-button v-b-modal.modal-4 size="sm" variant="primary" class="btn" v-b-tooltip.hover.top title="Read More">
            Help <b-icon icon="question-circle-fill" aria-label="Help"></b-icon>
          </b-button>
          <b-modal id="modal-4" title="Formulae used in MAB">
            <h3 id="bkmrk-soil-moisture-status">Soil Moisture Status</h3>
            <p id="bkmrk-soil-moisture-status-0">A percentage that indicates how optimal the current field's soil
              moisture value is with respect to the field's capacity, it's active growth stage (if defined) and the
              cultivar.<br><br>Some definitions:</p>
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                  <tr>
                    <td><strong>Term</strong></td>
                    <td><strong>Definition</strong></td>
                  </tr>
                  <tr>
                    <td>Wilt</td>
                    <td>(of a plant, leaf, or flower) become limp through heat, loss of water, or
                      disease; droop.</td>
                  </tr>
                  <tr>
                    <td>Wilt point</td>
                    <td>The minimal point of soil moisture the plant requires not to wilt.</td>
                  </tr>
                  <tr>
                    <td>Full</td>
                    <td>A Field's Maximum Safe Soil Moisture Percentage.</td>
                  </tr>
                  <tr>
                    <td>Upper</td>
                    <td>The Upper Value is an adjusted Full value</td>
                  </tr>
                  <tr>
                    <td>Refill</td>
                    <td>A Field's Minimum Safe Soil Moisture Percentage.</td>
                  </tr>
                  <tr>
                    <td>Lower</td>
                    <td>The Lower Value is an adjusted Refill value</td>
                  </tr>
                  <tr>
                    <td>Field Capacity</td>
                    <td>A Field's Moisture Capacity (As a Soil Moisture Percentage).</td>
                  </tr>
                  <tr>
                    <td>Growth Stage</td>
                    <td>A particular stage in a plant's development. It's a Start Date and a
                      Duration (in days).</td>
                  </tr>
                  <tr>
                    <td>Active Growth Stage</td>
                    <td>A Growth Stage is said to be 'Active' if today's date falls within its
                      Start Date + Duration range.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p>The Status calculation is dependent on whether a field has growth stages
              defined <strong>and</strong> whether the current date falls within the active growth stage's range (start
              date + duration).</p>
            <p>If a field has no growth stages defined, or, if there's no active growth
              stage, then the status is calculated as follows:</p>
            <ul class="list-group">
              <li class="list-group-item">Upper Value = Full</li>
              <li class="list-group-item">Lower Value = Refill</li>
              <li class="list-group-item">capCapacityacity = Full - Refill</li>
              <li class="list-group-item"><strong>Status </strong>= (( Moisture - Lower Value ) / Capacity) * 100</li>
            </ul>
            <p>Otherwise if a field has an active growth stage defined then the status
              is calculated as follows:</p>
            <ul class="list-group">
              <li class="list-group-item">Capacity = Full - Refill</li>
              <li class="list-group-item">Upper Value = Refill&nbsp; + ( Capacity * ( Upper Value of Active Stage / 100 )
                )</li>
              <li class="list-group-item">Lower Value = Refill&nbsp; + ( Capacity * ( Lower Value of Active Stage / 100 )
                )</li>
              <li class="list-group-item">Capacity = Upper Value - Lower Value</li>
              <li class="list-group-item"><strong>Status</strong> = (( Moisture - Lower Value ) / Capacity ) * 100</li>
            </ul>
            <p><strong>Note:</strong> for both cases above, <strong>moisture</strong>
              is either the average or accumulative value from the latest soil moisture reading, depending on the
              configured graph model (see Cultivars).</p>
            <h3>Irrigation Recommendation (IrriRec)</h3>
            <p>To calculate the IrriRec, the soil moisture status value needs to be valid (within 0
              and a 100 range). The upper_value of the status calculation is used when calculating the IrriRec value, so
              they are related.</p>
            <p>The IrriRec also can only be calculated when the following points are
              satisfied:</p>
            <ul class="list-group">
              <li class="list-group-item">Upper Value &gt; Moisture</li>
              <li class="list-group-item">Norm Irrigation &gt; 0</li>
              <li class="list-group-item">Norm Reaction &gt; 0</li>
            </ul>
            <p>Otherwise, it receives a default value of <strong>0</strong>.</p>
            <p>The IrriRec is calculated as follows:</p>
            <ul class="list-group">
              <li class="list-group-item">Irrigation Ratio = Norm Irrigation / Norm Reaction</li>
              <li class="list-group-item"><strong>Irri. Rec</strong> = ( Upper Vallue - Moisture ) * Irrigation Ratio</li>
            </ul>
            <!-- <h3>3 Day Depletion Rates</h3>
              <p><br></p>
              <h3>Nutrient Averages</h3>
              <p><br></p>
              <p><br></p> -->
          </b-modal>
        </template>

        <b-row class='nomargin'>
          <b-col md>
            <b-form-select v-model="perPage" @change="loadDashboardData">
              <b-form-select-option :value="5">5</b-form-select-option>
              <b-form-select-option :value="10">10</b-form-select-option>
              <b-form-select-option :value="25" selected>25</b-form-select-option>
              <b-form-select-option :value="50">50</b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <b-form-select class="select-primary pagination-select" v-model="filterNodeType" @change="loadDashboardData"
              placeholder="Node Type">
              <b-form-select-option class="select-primary" v-for="(item, index) in nodeTypes" :key="index"
                :label="item.label" :value="item.value"></b-form-select-option>
            </b-form-select>
          </b-col>
          <b-col md>
            <base-input prepend-icon="fas fa-search">
              <b-input v-model="filterText" :debounce="1000" @update="loadDashboardData" placeholder="Search...">
              </b-input>
            </base-input>
          </b-col>
        </b-row>

        <b-row class='nomargin'>
          <b-col md>
            <b-table striped bordered outlined small stacked="lg" responsive show-empty primary-key="node_address"
              no-local-sorting @sort-changed="sortingChanged" :fields="tableColumns" :items="tableData"
              :busy.sync="bInitialQuery">

              <template #cell(field_name)="data">
                <strong>
                  <router-link v-b-tooltip.hover.top title="Go to this field's graph data"
                    v-if="userCan('Graph', convertNodeTypeToSubsystem(data.item.node_type), data.item.id, 'O')" :to="{
                      name: convertNodeTypeToGraphRouteName(data.item.node_type), // meters_graph, well_controls_graph
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
              Showing {{ Math.min(1 + (perPage * (currentPage - 1)), totalRows) }} to {{ Math.min((perPage * (currentPage
                - 1))
                + perPage, totalRows) }} of {{ totalRows }} entries
            </b-col>
            <b-col md>
              <b-pagination @input="loadDashboardData" v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                align="right"></b-pagination>
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

export default {

  mixins: [mab_utils],

  data() {
    return {
      loading: false,
      tableLoading: false,
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
          label: 'Node',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'date_time',
          label: 'Last Update',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'status',
          label: 'Status %',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'irri_rec',
          label: 'Irri. rec.',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'eto',
          label: 'ETo',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'one',
          label: '24h',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'two',
          label: '48h',
          sortable: true,
          tdClass: 'valign'
        },
        {
          key: 'three',
          label: '72h',
          sortable: true,
          tdClass: 'valign'
        }
      ],
      tableData: [],
      totalRows: 1,
      currentPage: 1,
      perPage: 25,
      filterText: '',
      sortBy: this.isAdmin() ? 'node_address' : 'date_time',
      sortDir: 'desc',

      filterNodeType: 'Soil Moisture',
      nodeTypes: [
        { label: 'All Types', value: 'All' },
        { label: 'Soil Moisture', value: 'Soil Moisture' },
        { label: 'Nutrients', value: 'Nutrients' },
        { label: 'Wells', value: 'Wells' },
        { label: 'Meters', value: 'Water Meter' }
      ],
      refreshTimer: null,
      bInitialQuery: true
    };
  },
  methods: {
    loadDashboardData() {
      this.loading = true;

      this.$axios.post("/api/dashboard_table", {
        cur_page: this.currentPage,
        per_page: this.perPage,
        initial: this.bInitialQuery,
        node_type: this.filterNodeType,
        filter: this.filterText,
        sort_by: this.sortBy,
        sort_dir: this.sortDir ? 'desc' : 'asc'
      }).then(resp => {

        this.loading = false;

        // if(this.isAdmin()){
        //   this.tableColumns.unshift({
        //     key: 'data_source',
        //     label: 'Data Source',
        //     sortable: true,
        //     tdClass: 'valign'
        //   });
        // }

        if (this.isAdmin() || this.userLimits('View', 'Dashboard', 'C').length > 1) {
          this.tableColumns.unshift({
            key: 'company_name',
            label: 'Entity',
            sortable: true,
            tdClass: 'valign'
          });
        }

        this.tableData = resp.data.rows;
        this.totalRows = resp.data.total;

        if (this.totalRows == 0) { this.currentPage = 1; }

        if (this.bInitialQuery) {
          this.bInitialQuery = false;
          this.refreshTimer = setInterval(() => { this.loadDashboardData(); }, 60000 * 5);
        }
      });
    },

    sortingChanged(e) {
      this.sortBy = e.sortBy;
      this.sortDir = e.sortDesc;
      this.loadDashboardData();
    }

  },

  mounted() {
    this.loadDashboardData();
  },

  // filter change reset page
  watch: { filterText: function (n, o) { this.currentPage = n != o ? 1 : this.currentPage } },

  beforeRouteLeave(to, from, next) {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }
    next();
  }
};
</script>
<style>
.no-border-card .card-footer {
  border-top: 0;
}

.modal-title {
  font-size: 2.0625rem;
}

.list-group-item {
  color: #525f7f;
  font-size: .8125rem;
  white-space: nowrap;
}
</style>
