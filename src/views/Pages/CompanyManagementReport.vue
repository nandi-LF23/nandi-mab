<template>
  <div class="content">
    <base-header class="pb-6" type="company">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5">
            Entity Management - Reporting
          </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <div class='row'>
        <div class='col-md-12'>
          <b-card>
            <b-card-text>
              <h3>Device Placement</h3>

              <b-row>
                <b-col md>
                  <base-input label="Year">
                    <el-select @change="loadReport" v-model="report_params.year">
                      <template v-for="(year,index) in years">
                        <el-option :key="index" :label="year" :value="year"></el-option>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Entities">
                    <el-select @change="loadReport" v-model="report_params.company_ids" value-key="id" multiple filterable collapse-tags>
                      <template v-for="(company,index) in companies">
                        <el-option :key="index" :label="company.company_name" :value="company.id"></el-option>
                      </template>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Detailed">
                    <b-form-checkbox @change="loadReport" v-model="report_params.detailed" :value="true" :unchecked-value="false" size="lg">
                      Detailed Report
                    </b-form-checkbox>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Export">
                    <base-button
                      :disabled="!userCan('Report', 'Entities') || loading || report_params.company_ids.length == 0"
                      @click.native="getPdfReport()"
                      class="btn"
                      size="sm"
                      type="primary"
                      icon>
                      Export to PDF
                    </base-button>
                  </base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <h3 v-if="report_params.detailed">Details</h3>
                  <b-table-simple striped responsive v-if="report_params.detailed">
                    <b-thead>
                      <b-tr>
                        <b-th class='text-left'>Distributor</b-th>
                        <b-th class='text-left'>Subsidiary</b-th>
                        <b-th class='text-left'>Node Type</b-th>
                        <b-th class='text-right'>Qty</b-th>
                        <b-th class='text-left'>Field</b-th>
                        <b-th class='text-left'>Node Address</b-th>
                        <b-th class='text-right'>Node EST</b-th>
                      </b-tr>
                    </b-thead>
                    <b-tbody>
                      <template v-for="row in details">
                        <b-tr>
                          <b-td>{{ row.parent_co }}</b-td>
                          <b-td>{{ row.subsidiary }}</b-td>
                          <b-td>{{ row.node_type }}</b-td>
                          <b-td class='text-right'>{{ row.node_count }}</b-td>
                          <b-td>{{ row.field_name }}</b-td>
                          <b-td>{{ row.node_address }}</b-td>
                          <b-td class='text-right'>{{ row.node_est }}</b-td>
                        </b-tr>
                      </template>
                    </b-tbody>
                  </b-table-simple>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <h3 responsive v-if="summary && totals">Summary</h3>
                  <b-table-simple striped responsive v-if="summary && totals">
                    <b-thead>
                      <b-tr>
                        <b-th class='text-left'>Distributor</b-th>
                        <b-th class='text-left'>Subsidiary</b-th>
                        <b-th class='text-right' v-if="totals.sm_total">Soil Moisture</b-th>
                        <b-th class='text-right' v-if="totals.nt_total">Nutrients</b-th>
                        <b-th class='text-right' v-if="totals.wl_total">Well Controls</b-th>
                        <b-th class='text-right' v-if="totals.mt_total">Meters</b-th>
                        <b-th class='text-right'>Line Total</b-th>
                      </b-tr>
                    </b-thead>
                    <b-tbody>
                      <template v-for="row in summary">
                        <b-tr>
                          <b-td>{{ row.parent_co }}</b-td>
                          <b-td>{{ row.subsidiary }}</b-td>

                            <template v-if="row.row_type == 'Data'">
                              <b-td v-if="totals.sm_total" class='text-right'><strong>{{ row.sm_count }}</strong></b-td>
                              <b-td v-if="totals.nt_total" class='text-right'><strong>{{ row.nutrients_count }}</strong></b-td>
                              <b-td v-if="totals.wl_total" class='text-right'><strong>{{ row.wells_count }}</strong></b-td>
                              <b-td v-if="totals.mt_total" class='text-right'><strong>{{ row.meters_count }}</strong></b-td>
                            </template>
                            <template v-else-if="row.row_type == 'Subtotal' || row.row_type == 'Total'">
                              <b-td :colspan="popcols" class='text-right'>{{ row.row_type }}</b-td>
                            </template>
                            <b-td class='text-right'>{{ row.line_total }}</b-td>

                        </b-tr>
                      </template>
                    </b-tbody>
                  </b-table-simple>
                </b-col>
              </b-row>

            </b-card-text>
          </b-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Modal from '@/components/Modal'
import { Select, Option } from 'element-ui'
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    Modal
  },

  data() {
    return {
      loading: false,
      initial: true,

      summary: [],
      totals:  [],
      popcols: 0,
      details: [],
      years: [],
      companies: [], // all distributor company ids

      report_params: {
        type: 'json',
        company_ids: [], // selected distributor/subsidiary company ids
        year: 'Total',
        detailed: false,
        mode: 'dist',
        initial: true
      }

    };
  },

  methods: {

    loadReport()
    {
      this.$axios.post("/api/company_report", this.report_params)
      .then((resp) => {
        this.loading = false;
        this.report_params.initial = false;
        this.summary = resp.data.summary;
        this.totals  = resp.data.totals;

        this.popcols = 0;
        this.popcols += this.totals.sm_total > 0 ? 1 : 0;
        this.popcols += this.totals.nt_total > 0 ? 1 : 0;
        this.popcols += this.totals.wl_total > 0 ? 1 : 0;
        this.popcols += this.totals.mt_total > 0 ? 1 : 0;

        if(resp.data.details){
          this.details = resp.data.details;
        }
        if(resp.data.years){
          this.years = resp.data.years;
        }
        this.companies = resp.data.companies;
        if(this.initial){
          this.report_params.company_ids = resp.data.cc_ids;
          this.initial = false;
        }
      });
    },

    getPdfReport(){

      const params = Object.assign({}, this.report_params);
      params.type = 'pdf';

      this.$axios.post("/api/company_report", params)
      .then((resp) => {
        this.loading = false;
        if(resp.data.url){
          window.open(resp.data.url);
        }
      });
      
    },
  },

  created()
  {
    this.loadReport();
  }
};
</script>
<style>
</style>
