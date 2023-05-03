<template>
  <div class="content">
    <base-header class="pb-6" type="company">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show="company_model.company_name">
            {{ 'Entity Management - ' + company_model.company_name }}
          </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <div class='row'>
        <div class='col-md-12'>
          <b-card header-tag="header" no-body>
            <template #header>
              <base-button
                :disabled="!userCan('Lock', 'Entities') || loading"
                @click.native="company_model.is_locked ? confirmUnlockEntity() : confirmLockEntity()"
                class="btn"
                size="sm"
                type="primary"
                icon>
                {{ company_model.is_locked ? "Unlock Entity" : "Lock Entity" }}
              </base-button>
            </template>

            <b-tabs card v-model="tab_index">
              <b-tab title="Details" active ref="details_tab">
                <b-card-text>
                  <validation-observer ref='form' slim>
                    <form role="form" @submit.prevent="()=>false">
                      <b-row>
                        <b-col md>
                          <h4 class="mb-1">Basic Info</h4>
                          <base-input
                            label="Entity Name"
                            @change="syncFields"
                            name="company name"
                            rules="required"
                            placeholder="Name"
                            vid="company_name"
                            v-model="company_model.company_name">
                          </base-input>

                          <base-input label="Entity Logo">
                            <ImageCropUpload
                              :stencilProps="{ scalable: true, movable: true/*, aspectRatio: 2/1*/ }"
                              :fileData="company_model.company_logo"
                              :resize="{ height: 32, width: 'auto' }"
                              @done="updateLogo" @clear="updateLogo"/>
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Contact Name"
                            name="contact name"
                            rules="required"
                            placeholder="Contact Name"
                            vid="contact_name"
                            v-model="company_model.contact_name">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Contact Email"
                            name="contact email"
                            placeholder="Contact Email"
                            vid="contact_email"
                            v-model="company_model.contact_email">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Contact Phone"
                            name="contact phone"
                            placeholder="Contact Phone"
                            vid="contact_phone"
                            v-model="company_model.contact_phone">
                          </base-input>

                        </b-col>
                        <b-col md>
                          <h4 class="mb-1">Physical Address</h4>
                          <base-input
                            @change="syncFields"
                            label="Line 1"
                            name="physical address line 1"
                            placeholder="Line 1"
                            vid="physical_address.line_1"
                            v-model="company_model.physical_address.line_1">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Line 2"
                            name="physical address line 2"
                            placeholder="Line 2"
                            vid="physical_address.line_2"
                            v-model="company_model.physical_address.line_2">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Town/City"
                            name="physical city"
                            placeholder="Town/City"
                            vid="physical_address.city"
                            v-model="company_model.physical_address.city">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Postal Code"
                            name="physical postal code"
                            placeholder="Postal Code"
                            vid="physical_address.postalcode"
                            v-model="company_model.physical_address.postalcode">
                          </base-input>

                          <base-input
                            label="Physical Country"
                            name="physical country"
                            placeholder="Physical Country"
                            vid="physical_address.country">
                            <el-select
                              @change="syncFields"
                              v-model="company_model.physical_address.country"
                              filterable
                              placeholder="Country">
                              <el-option
                                v-for="item in countries"
                                :key="item.code"
                                :label="item.name"
                                :value="item.code">
                              </el-option>
                            </el-select>
                          </base-input>
                        </b-col>
                        <b-col md>
                          <h4 class="mb-1">Billing Address</h4>
                          <base-input
                            @change="syncFields"
                            label="Line 1"
                            name="billing address line 1"
                            placeholder="Line 1"
                            vid="billing_address.line_1"
                            v-model="company_model.billing_address.line_1">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Line 2"
                            name="billing address line 2"
                            placeholder="Line 2"
                            vid="billing_address.line_2"
                            v-model="company_model.billing_address.line_2">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Town/City"
                            name="billing city"
                            placeholder="Town/City"
                            vid="billing_address.city"
                            v-model="company_model.billing_address.city">
                          </base-input>

                          <base-input
                            @change="syncFields"
                            label="Postal Code"
                            name="billing postal code"
                            placeholder="Postal Code"
                            vid="billing_address.postalcode"
                            v-model="company_model.billing_address.postalcode">
                          </base-input>

                          <base-input 
                            label="Billing Country"
                            name="billing country"
                            placeholder="Billing Country"
                            vid="billing_address.country">
                            <el-select
                              @change="syncFields"
                              v-model="company_model.billing_address.country"
                              filterable
                              placeholder="Country">
                              <el-option
                                v-for="item in countries"
                                :key="item.code"
                                :label="item.name"
                                :value="item.code">
                              </el-option>
                            </el-select>
                          </base-input>
                        </b-col>
                      </b-row>
                    </form>
                  </validation-observer>
                </b-card-text>
              </b-tab>

              <b-tab title="Options" ref="options_tab">
                <b-card-text>
                    <b-row>
                      <template v-for="(options, group) in company_options">
                        <b-col md='4' :key="group">
                          <b-card>
                            <template #header>
                              <h3 class="mb-0">{{ group }}</h3>
                            </template>
                            <b-card-text>
                              <template v-for="(option, index) in options">

                                <template v-if="option.type == 'text'">
                                  <b-form-group
                                    :key="index"
                                    :label="option.label"
                                    :label-for="option.slug"
                                    :description="option.desc">
                                    <b-form-input 
                                      @change="syncOptions"
                                      :id="option.slug"
                                      v-model="option.value">
                                    </b-form-input>
                                  </b-form-group>
                                </template>

                                <template v-else-if="option.type == 'textarea'">
                                  <b-form-group
                                    :key="index"
                                    :label="option.label"
                                    :label-for="option.slug"
                                    :description="option.desc">
                                    <b-form-textarea
                                      @change="syncOptions"
                                      :id="option.slug"
                                      v-model="option.value">
                                    </b-form-textarea>
                                  </b-form-group>
                                </template>

                                <template v-else-if="option.type == 'colorpicker'">
                                  <b-form-group
                                    :key="index"
                                    :label="option.label"
                                    :label-for="option.slug"
                                    :description="option.desc">
                                    <b-form-input
                                      @change="syncOptions"
                                      :id="option.slug"
                                      v-model="option.value"
                                      type="color">
                                    </b-form-input>
                                  </b-form-group>
                                </template>

                              </template>
                            </b-card-text>
                          </b-card>
                        </b-col>
                      </template>
                    </b-row>
                </b-card-text>
              </b-tab>

              <b-tab title="Objects" ref="objects_tab">
                <b-card>
                  <template #header>
                    <h3 class="mb-0">Users <b-badge v-b-tooltip.hover.top title="Current users of this entity.">{{ users.length }}</b-badge></h3>
                  </template>
                  <b-card-text>
                    <b-row>
                      <b-col md>
                        <b-button
                          v-for="(item, idx) in users"
                          :to="'/users_manage/edit/' + item.label"
                          :disabled="!userCan('Edit', 'Users', item.id, 'O')"
                          class="mb-2"
                          variant="success"
                          size="sm"
                          :key="idx">
                          {{ item.label }}
                        </b-button>
                      </b-col>
                    </b-row>
                  </b-card-text>
                </b-card>

                <b-card>
                  <template #header>
                    <h3 class="mb-0">Groups <b-badge v-b-tooltip.hover.top title="Current groups of this entity.">{{ groups.length }}</b-badge></h3>
                  </template>

                  <b-card-text>
                    <b-row>
                      <b-col md>
                        <b-button
                          v-for="(item, idx) in groups"
                          :to="'/groups_manage'"
                          :disabled="!userCan('Edit', 'Groups', item.id, 'O')"
                          class="mb-2"
                          variant="success"
                          size="sm"
                          :key="idx">
                          {{ item.label }}
                        </b-button>
                      </b-col>
                    </b-row>
                  </b-card-text>
                </b-card>

                <b-card>
                  <template #header>
                    <h3 class="mb-0">Roles <b-badge v-b-tooltip.hover.top title="Current roles of this entity.">{{ roles.length }}</b-badge></h3>
                  </template>

                  <b-card-text>
                    <b-row>
                      <b-col md>
                        <b-button
                          v-for="(item,idx) in roles"
                          :to="'/roles_manage/edit/' + item.id"
                          :disabled="!userCan('Edit', 'Roles', item.id, 'O')"
                          class="mb-2"
                          variant="success"
                          size="sm"
                          :key="idx">
                          {{ item.label }}
                        </b-button>
                      </b-col>
                    </b-row>
                  </b-card-text>
                </b-card>

                <b-card>
                  <template #header>
                    <h3 class="mb-0">Nodes <b-badge v-b-tooltip.hover.top title="Current nodes of this entity.">{{ nodes.length }}</b-badge></h3>
                  </template>
                  <b-card-text>
                    <b-row>
                      <b-col md>
                        <b-button
                          v-for="(item,idx) in nodes"
                          :to="item.link"
                          :disabled="!userCan('Edit', 'Node Config', item.id, 'O') && !userCan('Edit', item.subsystem, item.id, 'O')"
                          class="mb-2"
                          variant="success"
                          size="sm"
                          :key="idx">
                          {{ item.label }}
                        </b-button>
                      </b-col>
                    </b-row>
                  </b-card-text>
                </b-card>

                <b-card>
                  <template #header>
                    <h3 class="mb-0">Sensor Types <b-badge v-b-tooltip.hover.top title="Current sensor types of this entity.">{{ sensor_types.length }}</b-badge></h3>
                  </template>
                  <b-card-text>
                    <b-row>
                      <b-col md>
                        <b-button
                          v-for="(item,idx) in sensor_types"
                          :to="'/sensor_types/edit/' + item.id"
                          :disabled="!userCan('Edit', 'Sensor Types', item.id, 'O')"
                          class="mb-2"
                          variant="success"
                          size="sm"
                          :key="idx">
                          {{ item.label }}
                        </b-button>
                      </b-col>
                    </b-row>
                  </b-card-text>
                </b-card>
              </b-tab>

              <b-tab title="Reporting" v-if="userCan('Report', 'Entities')" ref="reporting_tab">

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
                      <base-input label="Detailed">
                        <b-form-checkbox @change="loadReport" v-model="report_params.detailed" :value="true" :unchecked-value="false" size="lg">
                          Detailed Report
                        </b-form-checkbox>
                      </base-input>
                    </b-col>
                    <b-col md>
                      <base-input label="Export">
                        <base-button
                          :disabled="!userCan('Report', 'Entities') || loading"
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
                      <b-table-simple striped responsive v-if="summary">
                        <b-thead>
                          <b-tr>
                            <b-th class='text-right' v-if="totals.sm_total">Soil Moisture</b-th>
                            <b-th class='text-right' v-if="totals.nt_total">Nutrients</b-th>
                            <b-th class='text-right' v-if="totals.wl_total">Well Controls</b-th>
                            <b-th class='text-right' v-if="totals.mt_total">Meters</b-th>
                            <b-th class='text-right'>Line Total</b-th>
                          </b-tr>
                        </b-thead>
                        <b-tbody>
                          <template v-for="(row, sidx) in summary">
                            <b-tr :key="sidx">

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

              </b-tab>

              <b-tab title="Integrations" v-if="userCan('Integrate', 'Entities')" ref="integrations_tab">
                <b-card  v-for="(integration, slug) in integrations" :key="slug">
                  <template #header>
                    <h3 class="mb-0">{{ integration.name }}</h3>
                  </template>
                  <b-card-text>
                    <b-row align-v='center'>
                      <b-col>
                        <b-toast :visible="true" title="Status" static no-auto-hide no-close-button>
                          {{ integration.status_text }}
                        </b-toast>
                      </b-col>
                      <b-col>
                        <b-button variant="success" @click="handleLink(integration)">
                          {{ integration.status_button }}
                        </b-button>
                      </b-col>
                    </b-row>

                    <template v-if="company_model.integrations && Object.keys(company_model.integrations).length">
                      <b-row :key="slug">
                        <b-col>
                          <hr></hr>
                          <h4>{{ slug }} Options</h4>
                        </b-col>
                      </b-row>
                      <b-row>
                        <b-col v-for="(opt, opt_key) in company_model.integrations[slug]['entities_manage']" :key="opt_key" sm="4">
                          <base-input v-if="opt.type == 'text'" :label="opt.label">
                            <b-form-input @change="syncFields" v-model="opt.value">
                              {{ opt.desc ? opt.desc : opt.label }}
                            </b-form-input>
                          </base-input>
                        </b-col>
                      </b-row>
                    </template>
                  </b-card-text>
                </b-card>
              </b-tab>

            </b-tabs>
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
import ImageCropUpload from "../../custom_components/ImageCropUpload";
import countryList from "../../util/countryList";

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    Modal,
    ImageCropUpload
  },
  
  data() {
    return {

      tab_index: 0,

      loading: false,
      initial: true,

      countries: countryList,

      company_model: {

        id: null,

        company_name: '',
        company_logo: '',

        contact_name: '',
        contact_email: '',
        contact_phone: '',

        physical_address: {
          line_1: '',
          line_2: '',
          city: '',
          postalcode: '',
          country: ''
        },

        billing_address: {
          line_1: '',
          line_2: '',
          city: '',
          postalcode: '',
          country: ''
        },

        integrations: null, /* entity_manage options */
        is_locked: 0,
        is_distributor: 0

      },

      users: [],
      groups: [],
      roles: [],
      nodes: [],
      sensor_types: [],
      integrations: [], /* integration records */
      company_options: [],

      refreshTimer: null,

      summary: [],
      totals:  [],
      popcols: 0,    // always 0 (no dist or sub cols in this report)
      details: [],
      years: [],

      report_params: {
        type: 'json',
        company_ids: [ this.$route.params.id ], // always a single company
        year: 'Total',
        detailed: false,
        mode: 'sub',
        initial: true
      }

    };
  },

  created()
  {
    this.loadCompany();
  },

  mounted(){
    if(mabRedirect && mabRedirect.length && mabRedirect == 'integrate'){
      setTimeout(() => {
        this.$refs.integrations_tab.activate();
        mabRedirect = '';
      }, 10);
    }
  },

  methods: {

    loadCompany()
    {
      this.loading = true;
      this.$axios.get("/api/company/" + this.$route.params.id)
      .then((resp) => {
        this.loading = false;
        this.company_model = resp.data.company;
        this.integrations = resp.data.integrations;
        this.loading = true;
        this.$axios.get("/api/company_objects/" + this.$route.params.id)
        .then((resp) => {
          this.loading = false;
          this.users   = resp.data.objects.users;
          this.groups  = resp.data.objects.groups;
          this.roles   = resp.data.objects.roles;
          this.nodes   = resp.data.objects.nodes;
          this.sensor_types = resp.data.objects.sensors;
        });
      });
      this.loadReport();
      this.loadCompanyOptions();
    },

    loadCompanyOptions()
    {
      this.loading = true;
      this.$axios.get("/api/get_company_opts/" + this.$route.params.id)
      .then((resp) => {
        this.loading = false;
        this.company_options = resp.data.options;
      });
    },

    loadReport()
    {
      this.$axios.post("/api/company_report", this.report_params)
      .then((resp) => {
        this.loading = false;
        this.report_params.initial = false;
        this.summary = resp.data.summary;
        this.totals = resp.data.totals;

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
        if(this.initial){
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

    refreshCompany()
    {
      this.loading = true;
      return this.$axios.get("/api/company/" + this.$route.params.id)
      .then((resp) => {
        this.loading = false;
        this.company_model = resp.data.company;
        this.integrations = resp.data.integrations;
        return resp;
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

        this.$axios.post("/api/company_update", this.company_model)
        .then((resp) => {
          this.loading = false;
          if(resp.data.message == 'company_updated'){
            this.$notify({
              title: 'Saved',
              message: 'Changes were saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.message == 'nonexistent'){
            this.$notify({
              title: 'Error',
              message: 'Entity not found (might have been removed)',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          console.log(err);
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
        });

      });
    },

    syncOptions(){
      this.loading = true;
      this.$axios.post("/api/upd_company_opts", {
        company_id: this.company_model.id,
        options: this.company_options
      }).then((resp) => {
        this.loading = false;
        if(resp.data.message == 'options_updated'){
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
        console.log(err);
        if(err.response.data.errors){
          this.$refs.form.setErrors(err.response.data.errors);
        }
      });
    },

    updateLogo(data){
      console.log(data);
      this.company_model.company_logo = data;
      this.syncFields();
    },

    handleLink(integration){
      if(integration.status_button == 'Connect'){
        this.newTab(integration.link, integration.name);
      } else if(integration.status_button == 'Disconnect'){
        this.loading = true;
        this.$axios.get(integration.link)
        .then((resp) => {
          this.loading = false;
          if(resp.data.result == 'token_revoked'){
            this.refreshCompany();
            this.$notify({
              title: 'Success',
              message: "Token was revoked",
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        });
      }
    },

    confirmLockEntity(){
      swal.fire({
        title: 'Lock Entity',
        text: 'Please confirm entity lock',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Lock Entity',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.lockEntity(this.company_model.id);
        }
      });
    },

    confirmUnlockEntity(){
      swal.fire({
        title: 'Unlock Entity',
        text: 'Please confirm entity unlock',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Unlock Entity',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.unlockEntity(this.company_model.id);
        }
      });
    },

    lockEntity(company_id)
    {
      this.loading = true;

      this.$axios.post("/api/company_lock", { company_id })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'company_locked'){
          this.company_model.is_locked = 1;
          this.$notify({
            title: 'Success',
            message: 'Entity has been locked',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      }).catch(err => {
        this.loading = false;
        console.log(err);
      });
    },

    unlockEntity(company_id)
    {
      this.loading = true;

      this.$axios.post("/api/company_unlock", { company_id })
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'company_unlocked'){
          this.company_model.is_locked = 0;
          this.$notify({
            title: 'Success',
            message: 'Entity has been unlocked',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      }).catch(err => {
        this.loading = false;
        console.log(err);
      });
    },

    newTab(url, name){
      if(url){
        window.open(url);
        this.refreshTimer = setInterval(() => {
          this.refreshCompany().then(() => {
            Object.keys(this.integrations).forEach(integration => {
              if(
                this.integrations[integration].name == name && 
                this.integrations[integration].status_text == 'Connected'
              ){
                clearInterval(this.refreshTimer);
              }
            });
          });
        }, 10000);
      }
    },
  }

};
</script>
<style>
.el-select { display: block; }
.invalid-feedback { position: absolute; }
.el-select .el-select__tags .el-tag {
  background: #00A04C;
}
.tab-pane {
  padding-top:2em;
}
</style>
