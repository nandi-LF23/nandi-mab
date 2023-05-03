<template>
  <div class="content">
    <base-header class="pb-6" type="users">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show="model.name">
            {{ ($store.state.user_obj.email == model.email ? 'Profile' : 'User') + ' - ' + model.name }}
          </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
      <div class='row'>
        <div class='col-md-12'>

          <!-- Reset Password Modal -->
          <b-modal v-model="showPWModal" centered no-close-on-esc no-close-on-backdrop>
            <template #modal-header="{ close }">
              <h6 slot="header" class="modal-title" id="modal-title-default">Change Password</h6>
            </template>
            
            <template #default="{ hide }">
              <validation-observer ref="pwform" slim>
                <base-input
                  name="password"
                  rules="required|min:8|confirmed:confirmation"
                  alternative
                  class="mb-3"
                  type="password"
                  placeholder="Password"
                  vid="password"
                  addon-left-icon="ni ni-lock-circle-open" v-model="pw1">
                </base-input>
                <base-input
                  name="confirmation"
                  rules="required|min:8"
                  alternative
                  type="password"
                  placeholder="Confirm Password"
                  vid="confirmation"
                  addon-left-icon="ni ni-lock-circle-open" v-model="pw2">
                </base-input>
              </validation-observer>
            </template>

            <template #modal-footer="{ ok, cancel, hide }">
                <base-button type="link" class="ml-auto" @click="closePWChangeModal">Close</base-button>
                <base-button type="primary" @click='updatePW'>Update</base-button>
            </template>
          </b-modal>

          <!-- Change User Entity -->
          <b-modal v-model="showUserEntityChangeModal" centered no-close-on-esc no-close-on-backdrop>
            <template #modal-header="{ close }">
              <h6 slot="header" class="modal-title" id="modal-title-default">Change User Entity</h6>
            </template>

            <template #default="{ hide }">
              <validation-observer ref='entity_change_form' slim>
                <form role="form" @submit.prevent="()=>false">
                  <b-row>
                    <b-col>
                      <b-toast :visible="true" variant="warning" title="Important" static no-auto-hide>
                        Changing a user's entity will cause:
                        <ul>
                          <li>Removal of the user from any security groups</li>
                          <li>Removal of the user from any integrations</li>
                        </ul>
                      </b-toast>
                    </b-col>
                  </b-row>
                  <b-row>
                    <b-col>
                      <div>
                        <base-input
                          label="Entity"
                          name="company"
                          rules="required"
                          vid="company"
                          placeholder="Entity"
                          autocomplete="off">
                          <el-select
                            :disabled="Object.values(companies).length < 2"
                            @change="companyChanged"
                            v-model="model.company_id"
                            filterable
                            placeholder="Entity"
                            v-b-tooltip.hover.top title="Change the user's owning entity. Note: After changing the entity, you have to also choose a new role before the changes would take effect."
                            autocomplete="off">
                            <template v-if="Object.values(companies).length">
                              <template v-for="company in companies">
                                <el-option 
                                  :key="company.id"
                                  :label="company.company_name"
                                  :value="company.id">
                                  <span>{{ company.company_name }}</span>
                                </el-option>
                              </template>
                            </template>
                          </el-select>
                        </base-input>
                      </div>
                    </b-col>
                  </b-row>
                  <b-row>
                    <b-col>
                      <div>
                        <base-input name="role" rules="required" label="Role" vid="role">
                          <el-select
                            v-model="model.role_id"
                            :disabled="!model.company_id || Object.keys(roles).length < 2"
                            filterable
                            placeholder="Role">
                            <el-option v-for="role in roles" :key="role.id" :label="role.role_name" :value="role.id"></el-option>
                          </el-select>
                        </base-input>
                      </div>
                    </b-col>
                  </b-row>
                </form>
              </validation-observer>
            </template>

            <template #modal-footer="{ ok, cancel, hide }">
                <b-button variant="outline-primary" class="ml-auto" @click="closeUserEntityChangeModal">Cancel</b-button>
                <b-button variant="primary" @click='changeUserEntity'>Change User Entity</b-button>
            </template>
          </b-modal>

          <b-card>
            <template #header>
              <base-button 
                :disabled="!userCan('Reset Password', 'Users') && model.email != $store.state.user_obj.email"
                @click.native="showPWChangeModal()"
                class="btn"
                size="sm"
                type="primary"
                icon>
                Change Password
              </base-button>

              <base-button
                v-if="isAdmin()"
                :disabled="model.is_admin"
                @click.native="confirmPromotion()"
                class="btn"
                size="sm"
                type="primary"
                icon>
                Promote
              </base-button>

              <base-button
                :disabled="!userCan('Lock', 'Users') || model.email == $store.state.user_obj.email || model.is_admin"
                @click.native="confirmUserLock()"
                class="btn"
                size="sm"
                type="primary"
                icon>
                {{ model.is_active ? 'Lock' : 'Unlock' }}
              </base-button>

            </template>

            <validation-observer ref='form' slim>
              <b-row>
                <b-col md>
                  <base-input @change="syncFields" name="name" rules="required" label="Full Name" placeholder="Full Name" vid="name" v-model="model.name"></base-input>
                </b-col>
                <b-col md>
                  <base-input @change="syncFields" name="email" rules="required|email" label="Email Address" placeholder="Email Address" vid="email" v-model="model.email"></base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <base-input @change="syncFields" label="Mobile" placeholder="Mobile" vid="mobile" v-model="model.mobile"></base-input>
                </b-col>
                <b-col md>
                  <base-input @change="syncFields" label="Physical Address" placeholder="Physical Address" vid="address" v-model="model.address"></base-input>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <base-input name="u.o.m" rules="required"  label="Unit of Measure" vid="unit_of_measure">
                    <el-select @change="syncFields" v-model="model.unit_of_measure" filterable placeholder="Unit of Measure">
                      <el-option label="Imperial" :value="2"></el-option>
                      <el-option label="Metric" :value="1"></el-option>
                    </el-select>
                  </base-input>
                </b-col>
                <b-col md>
                  <base-input label="Time Zone" name="timezone" rules="required" vid="timezone">
                    <el-select
                      v-if="timeZones.length"
                      v-model="model.timezone"
                      @change="syncFields"
                      filterable
                      placeholder="Time Zone">
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
                  <base-input name="role" rules="required" label="Role" vid="role">
                    <el-select
                      v-model="model.role_id"
                      @change="syncFields"
                      :disabled="!model.company_id || Object.keys(roles).length < 2"
                      filterable
                      placeholder="Role">
                      <el-option v-for="role in roles" :key="role.id" :label="role.role_name" :value="role.id"></el-option>
                    </el-select>
                  </base-input>
                </b-col>

                <b-col md>
                  <base-input label="Entity">
                    <b-input-group>
                      <b-form-input type="text" v-model="user_company_name" placeholder="Entity" readonly></b-form-input>
                      <b-input-group-append>
                        <b-button :disabled="!isAdmin()" variant="outline-primary" @click="openUserEntityChangeModal">Change Entity</b-button>
                      </b-input-group-append>
                    </b-input-group>
                  </base-input>
                </b-col>
              </b-row>

              <b-row v-if="model.company_id && (isAdmin() || isDistributor())">
                <b-col md>
                  <base-input name="is_distributor" vid="is_distributor">
                    <b-form-checkbox 
                      @change="syncFields"
                      size="lg"
                      v-model="model.is_distributor"
                      :disabled="!isAdmin() && ($store.state.user_obj.company_id == model.company_id || $store.state.user_obj.id == model.id)"
                      unchecked-value="0" value="1">
                      Distributor
                    </b-form-checkbox>
                  </base-input>
                </b-col>
              </b-row>
            </validation-observer>
          </b-card>

          <b-card v-if="model.company_id && (isAdmin() || isDistributor()) && model.is_distributor == '1'">
            <template #header>
              <h3 class="mb-0">Distributor Details</h3>
            </template>
            <b-row>
              <b-col md>
                <template v-if="dist_list && Object.keys(dist_list).length">
                  <base-input label="Managed Entities" name="managed_companies" vid="managed_companies">
                    <el-select
                      :disabled="!isAdmin() && ($store.state.user_obj.company_id == model.company_id || $store.state.user_obj.id == model.id)"
                      @change="syncFields"
                      v-model="model.managed_company_ids"
                      value-key="id"
                      multiple
                      filterable
                      placeholder="Choose">
                      <template v-if="dist_list && Object.keys(dist_list).length">
                        <el-option v-for="company in dist_list"
                          :key="company.id"
                          :label="company.company_name"
                          :value="company.id">
                        </el-option>
                      </template>
                    </el-select>
                  </base-input>
                </template>
                <template v-else>
                  <b-alert v-if="Object.keys(companies).length" variant="primary" show><strong>{{ companies['"'+model.company_id.toString()+'"'].company_name }}</strong> currently has no manageable subsidiary entities available to pre-assign to this distributor user.</b-alert>
                </template>
              </b-col>
            </b-row>
          </b-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Modal from '@/components/Modal'
import { Select, Option } from 'element-ui'
import mab_utils from '../../util/mab-utils';
import swal from 'sweetalert2';

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
      timeZones: [],
      roles: [],

      bInitialQuery: true,

      companies: [],
      dist_list: [],

      showPWModal: false,
      pw1: '',
      pw2: '',
      model: {
        id: null,
        name: '',
        email: '',
        mobile: '',
        address: '',
        unit_of_measure: '',
        timezone: '',
        role: '',
        is_admin: 0,
        is_distributor: 0,
        is_active: 0,
        role_id: null,
        company_id: null,
        password: null,
        parent_id: null,
        managed_company_ids: []
      },

      user_company_name: '',
      showUserEntityChangeModal: false,

      prev_company_id: null,
      prev_role_id: null
    };
  },
  methods: {

    loadUser()
    {
      this.loading = true;
      this.$axios.get("/api/user/" + this.$route.params.email)
      .then((resp) => {
        this.loading = false;
        if(resp.data.message == 'user_loaded'){

          this.model = resp.data.user;
          this.bInitialQuery = false;

        } else if(resp.data.message == 'nonexistent'){
          this.$notify({
            title: 'Error',
            message: 'User not found (might have been removed)',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        // should never happen
        } else if(resp.data.message == 'missing_email'){
          this.$notify({
            title: 'Error',
            message: "Parameter Error: email",
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
        this.loadCompanies();
        this.loadDistributors();
      });
    },

    loadCompanies()
    {
      this.$axios.post("/api/companies_list", {
        context: [ { verb: 'Edit', module: 'Users' } ]
      }).then((resp) => {
        this.companies = resp.data.companies;
        this.user_company_name = this.companies['"'+this.model.company_id.toString()+'"'].company_name;
        this.loadRoles();
      });
    },

    loadDistributors()
    {
      this.$axios.post('/api/companies_dist_list', {
        company_id: this.model.company_id
      }).then((resp) => {
        if(resp.data){
          this.dist_list = resp.data.companies;
        }
      })
    },

    loadRoles()
    {
      this.loading = true;
      this.$axios.post("/api/roles_by_cc", {
        company_id: this.model.company_id 
      }).then((resp) => {
        this.loading = false;
        this.roles = resp.data.roles;
      }); 
    },

    showPWChangeModal()
    {
      this.pw1 = '';
      this.pw2 = '';
      this.model.password = null;
      this.showPWModal = true;
    },

    closePWChangeModal()
    {
      this.pw1 = '';
      this.pw2 = '';
      this.model.password = null;
      this.showPWModal = false;
    },

    updatePW()
    {
      this.$refs.pwform.validate().then(success => {

        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please fill in all fields',
            type: 'warn',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;
        this.model.password = this.pw1;
        this.model.password_confirmation = this.pw2;

        this.$axios.post("/api/updatePW", this.model)
        .then((resp) => {
          this.loading = false;
          this.model.password = null;
          if(resp.data.message == 'pw_updated'){
            this.closePWChangeModal();
            this.$notify({
              title: 'Success',
              message: 'Password changed',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.pwform.setErrors(err.response.data.errors);
          }
        });
      });
    },

    promoteUser()
    {
      this.$axios.post("/api/promote", {
        id: this.model.id
      })
      .then((resp) => {
        if(resp.data.message == 'user_promoted')
        {
          this.$notify({
            title: 'Success',
            message: 'User was promoted',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
        }
      });
    },

    syncFields()
    {
      return this.$refs.form.validate().then(success => {

        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please fill in all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return;
        }

        this.loading = true;
        this.model.password = null;

        return this.$axios.post("/api/updatePW", this.model)
        .then((resp) => {
          this.loading = false;
          this.model.password = null;
          if(resp.data.message == 'user_updated'){

            if(resp.data.user){
              this.$store.dispatch('update_uom', resp.data.user.unit_of_measure);
            }
            
            this.$notify({
              title: 'Saved',
              message: 'Changes were saved',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            return resp;
          }
        }).catch(err => {
          this.loading = false;
          if(err.response.data.errors){
            this.$refs.form.setErrors(err.response.data.errors);
          }
          return err;
        });
      });
    },

    changeUserEntity()
    {
      this.$refs.entity_change_form.validate()
      .then(success => {

        if (!success) { 
          this.$notify({
            title: 'Required',
            message: 'Please fill in all fields',
            type: 'danger',
            verticalAlign: 'top',
            horizontalAlign: 'right',
            duration:1000,
            clean:true
          });
          return false;
        }

        this.syncFields()
        .then(() => {
          this.$notify({
            title: 'Success',
            message: 'User entity changed.',
            type: 'success',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
          this.showUserEntityChangeModal = false;
        }).catch(err => {
          this.$notify({
            title: 'Failure',
            message: 'User entity change failed.',
            type: 'warning',
            verticalAlign: 'top',
            horizontalAlign: 'right'
          });
          this.showUserEntityChangeModal = false;
        });

      });
    },

    confirmPromotion()
    {
      swal.fire({
        title: 'Promote User',
        text: `Please confirm user promotion`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Promote',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.promoteUser();
        }
      });
    },

    confirmUserLock()
    {
      swal.fire({
        title: this.model.is_active ? 'Lock User' : 'Unlock User',
        text: 'Please confirm ' + (this.model.is_active ? 'locking' : 'unlocking') + ' user account',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: this.model.is_active ? 'Lock' : 'Unlock',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.toggleUserLock();
        }
      });
    },

    toggleUserLock()
    {
      let verb = this.model.is_active ? 'lock' : 'unlock';
      this.$axios.post("/api/user_" + verb, { id: this.model.id })
      .then((resp) => {
        if(resp.data){
          if(resp.data.message == 'user_locked'){
            this.model.is_active = 0;
            this.$notify({
              title: 'Success',
              message: 'User account was locked',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          } else if(resp.data.message == 'user_unlocked'){
            this.model.is_active = 1;
            this.$notify({
              title: 'Success',
              message: 'User account was unlocked',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        }
      });
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
            //console.log(this.$store.state.user_obj.timezone);
            //console.log(this.timeZones);
          }
        }
      });
    },

    companyChanged()
    {
      this.model.role_id = null;
      this.loadRoles();
    },

    openUserEntityChangeModal()  {
      this.showUserEntityChangeModal = true;
      this.prev_company_id = this.model.company_id;
      this.prev_role_id = this.model.role_id;
    },

    closeUserEntityChangeModal() {
      this.showUserEntityChangeModal = false;
      this.model.company_id = this.prev_company_id;
      this.model.role_id = this.prev_role_id;
      // since we changed to another company and cancelled, we need to load the original roles again
      this.loadRoles();
    },

    restrictedNotice()
    {
      swal.fire({
        title: 'Access was restricted',
        text: 'You were redirected to your profile screen due to your account lacking the necessary permissions. Please notify your administrator.',
        showCancelButton: false,
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      });
    },

    loadAll(){
      this.loadTimezones();
      this.loadUser();
      if(mabRedirect && mabRedirect.length){
        console.log(mabRedirect);
        this.restrictedNotice();
        mabRedirect = '';
      }
    }
  },
  mounted()
  {
    this.loadAll();
  },
  watch: {
    // call again the method if the route changes
    '$route': 'loadAll'
  },
};
</script>
<style></style>
