<template>
  <div>

    <div class="header bg-success py-7 py-lg-8 pt-lg-9">
      <b-container class="container">
        <div class="header-body text-center mb-7">
          <b-row class="justify-content-center">
            <b-col xl="5" lg="6" md="8" class="px-5">
              <img class='logo' src="/img/exe/logo_white.svg">
            </b-col>
          </b-row>
        </div>
      </b-container>
    </div>
    
    <b-container class="mt--8 pb-5">
      <b-row class="justify-content-center">
        <b-col lg="5" md="7">
          <h1 class="text-white text-center"> {{ forgotpass ? 'Forgot Password' : ( resetpass ? 'Reset Password' : 'Login' ) }} </h1>
          <b-card no-body class="border-0 mb-0">
           
            <b-card-body class="px-lg-5 py-lg-5" align-v="center">
              
              <div v-if="maintenance" class='text-center'>
                <h2>WARNING</h2>
                <h3>Maintenance In Progress</h3>
                <base-button type="primary" @click='maintenance=false'>Proceed</base-button>
              </div>

              <validation-observer v-else ref="form" slim>
                <b-form role="form" @submit.prevent="onSubmit">

                  <template v-if="!companies">

                    <base-input
                      alternative
                      class="mb-3"
                      name="Email"
                      vid="email"
                      autocomplete="username email"
                      :rules="{ required: true, email: true }"
                      prepend-icon="ni ni-email-83"
                      placeholder="Email"
                      v-model="model.email">
                    </base-input>

                    <base-input v-if='!forgotpass'
                      alternative
                      class="mb-3"
                      autocomplete="current-password"
                      name="Password"
                      :rules="resetpass ? 'required|confirmed:confirmation' : 'required'"
                      prepend-icon="ni ni-lock-circle-open"
                      type="password"
                      vid="password"
                      :placeholder="resetpass ? 'New Password' : 'Password'"
                      v-model="model.password">
                    </base-input>

                    <base-input v-if='resetpass'
                      alternative
                      class="mb-3"
                      autocomplete="current-password"
                      name="Confirm Password"
                      rules="required"
                      prepend-icon="ni ni-lock-circle-open"
                      type="password"
                      placeholder="Confirm Password"
                      vid="confirmation"
                      v-model="model.password_confirmation">
                    </base-input>

                    <b-row>
                      <b-col cols='6'>
                        <b-form-checkbox v-if='!resetpass' v-model="forgotpass">Forgot Password</b-form-checkbox>
                      </b-col>
                      <b-col cols='6' class='text-right'>
                        <b-form-checkbox v-if='!resetpass && !forgotpass' v-model="singlemode" :disabled="singlemode_disabled">Single Mode</b-form-checkbox>
                      </b-col>
                    </b-row>

                  </template>

                  <template v-if="companies">
                    <el-select v-model="company" placeholder="Choose Entity">
                      <template v-for="(cc, idx) in companies">
                        <el-option :key="idx" :label="cc.label" :value="cc.id"></el-option>
                      </template>
                    </el-select>
                  </template>

                  <b-row>
                    <b-col cols='12'>
                      <b-alert :show="showNotice" :variant="noticeType">{{noticeText}}</b-alert>
                    </b-col>
                  </b-row>

                  <div class="text-center">
                    <base-button type="primary" native-type="submit" class="mt-4 mb-2" :disabled="singlemode && companies && !company">
                      {{  
                          forgotpass ? 'Send Reset Email' : (
                            resetpass ? 'Reset Password' : (
                              companies ? 'Proceed' : 'Sign in'
                            )
                          )
                      }}
                      <div v-show='loading' class='mab_spinner small light'></div>
                    </base-button>
                  </div>

                </b-form>
              </validation-observer>

            </b-card-body>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script>

import store from '@/store/store'
import axios from 'axios'
import { Select, Option } from 'element-ui'

export default {

  components: {
    [Select.name]: Select,
    [Option.name]: Option
  },

  store,

  data() {
    return {
      loading:false,
      
      model: {
        email: '',
        password: '',
        password_confirmation: ''
      },

      maintenance:false,
      forgotpass: false,
      resetpass:  false,
      singlemode: false,
      companies:  null,
      company:    null,
      singlemode_disabled: false,
      redirect: '',

      showNotice: false,
      noticeType: '',
      noticeText: '',
    };
  },
  created(){
    if(typeof mabResetAuth !== 'undefined'){
      if(mabResetAuth && mabResetAuth.token && mabResetAuth.token.length && mabResetAuth.email && mabResetAuth.email.length){
        this.resetpass = true;
        this.model.email = mabResetAuth.email;
      }
    }
    if(typeof mabContext !== 'undefined'){
      this.singlemode = mabContext ? true : false;
      this.singlemode_disabled = mabContext ? true : false;
    }
  },
  methods: {

    onSubmit() {

      this.$refs.form.validate().then(success => {
        if (!success) { return; }

        this.loading = true;

        if(this.forgotpass){

          axios.post('/api/forgot', {
            email: this.model.email,
            url: window.location.origin
          })
          .then(resp => {
            this.loading = false;
            if(resp.data.message){
              this.showNotice = true;
              this.noticeText = resp.data.message;
              this.noticeType = "success";
            }
          }).catch(err => {
            this.loading = false;
            this.showNotice = true;
            this.noticeText = err.response.data.message;
            this.noticeType = "danger";
          });

        } else if(this.resetpass){

          axios.post('/api/reset', {
            email: this.model.email,
            password: this.model.password,
            password_confirmation: this.model.password_confirmation,
            token: mabResetAuth.token
          })
          .then(resp => {
            this.loading = false;
            if(resp.data.message){
              this.showNotice = true;
              this.noticeText = resp.data.message;
              this.noticeType = "success";
              this.resetpass = false;
              mabResetAuth = null;
            }
          }).catch(err => {
            this.loading = false;
            this.showNotice = true;
            this.noticeText = err.response.data.message;
            this.noticeType = "danger";
          })

        } else if(this.companies){

          axios.post('/api/restrict', {
            restrict_to: this.company,
          })
          .then(resp => {
            this.loading = false;
            if(resp.data.message == 'success'){
              this.$router.replace('/' + this.redirect).catch((e) => { console.log(e); });
            }
          }).catch(err => {
            this.loading = false;
            this.showNotice = true;
            this.noticeText = err.response.data.message;
            this.noticeType = "danger";
          })

        } else {

          this.$store.dispatch('do_login', {
            'email': this.model.email,
            'password': this.model.password,
            'context': mabContext,
            'singlemode': this.singlemode
          })
          .then((resp) => {
            this.loading = false;
            this.showNotice = false;
            this.redirect = resp.data.redirect;
            if(resp.data.user.is_admin){
              this.$router.replace('/' + this.redirect).catch((e) => { console.log(e); });
            } else if(resp.data.companies){
              this.companies = resp.data.companies;
            }
          })
          .catch(err => {
            this.loading = false;
            this.noticeText = err.response.data.message
            this.noticeType = "danger"
            this.showNotice = true;
          });

        }
      });
    }
  }
}

</script>
<style>

  .logo {
    max-width:100%;
    height:auto;
  }

  .alert {
    margin-top:1rem;
    margin-bottom:0.5rem;
  }

  .el-select {
    width:100%;
  }

</style>