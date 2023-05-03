/*!

=========================================================
* BootstrapVue Argon Dashboard PRO - v1.0.0
=========================================================

* Product Page: https://www.creative-tim.com/product/bootstrap-vue-argon-dashboard-pro
* Copyright 2020 Creative Tim (https://www.creative-tim.com)

* Coded by www.creative-tim.com

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

*/
import Vue from 'vue';
import Axios from 'axios';
import Highcharts from 'highcharts';
import HighchartsVue from 'highcharts-vue'
import DashboardPlugin from './plugins/dashboard-plugin';
import App from './App.vue';
import loadExporting from 'highcharts/modules/exporting';
import offlineExporting from 'highcharts/modules/export-data';
import draggablePoints from 'highcharts/modules/draggable-points';
import HighchartsMore from "highcharts/highcharts-more";
import boost from "highcharts/modules/boost";
import VueCompositionAPI from "@vue/composition-api";
import swal from 'sweetalert2';

import 'mapbox-gl/dist/mapbox-gl.css';
import "v-mapbox/dist/v-mapbox.css";

Vue.config.productionTip = false;

// axios setup
Vue.prototype.$axios = Axios;
Vue.prototype.$axios.defaults.headers = {
  'Cache-Control': 'no-cache',
  'Pragma': 'no-cache',
  'Expires': '0',
};

// store setup
import store from './store/store';

// router setup
import router from './routes/router';

// highcharts config
loadExporting(Highcharts);
offlineExporting(Highcharts);
HighchartsMore(Highcharts);
draggablePoints(Highcharts);

// plugin setup
Vue.use(HighchartsVue);
boost(Highcharts);
Vue.use(DashboardPlugin);
Vue.use(VueCompositionAPI);

const mabvm = new Vue({
  el: '#app',
  render: h => h(App),
  router,
  store
});

// Add a response interceptor
Vue.prototype.$axios.interceptors.response.use(
  response => { 
    if(response.data && response.data.grants && typeof response.data.grants['__perms_meta__'] !== 'undefined'){
      store.dispatch('update_user_perms', response.data.grants['__perms_meta__']);
    }

    if(response.data && response.data.message && response.data.message == 'access_denied'){
      mabvm.$notify({
        title: 'Access Denied',
        message: 'Insufficient Permissions',
        type: 'danger',
        verticalAlign: 'top',
        horizontalAlign: 'right'
      });
    }
    return response;
  }, 
  error => {
    if (error && error.response && error.response.status === 401) {

      if(!store.getters.getAuthNotified){
        swal.fire({
          title: 'Session Expired',
          html: 'Your login session has expired.<br>Please login again.',
          showCancelButton: false,
          confirmButtonText: 'OK',
          buttonsStyling: false,
          customClass: {
            cancelButton: 'btn btn-outline-primary',
            confirmButton: 'btn btn-primary'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            store.dispatch('clear_token', {});
            mabvm.$router.push({ name: 'logout' });
          }
        })
      }

      return Promise.resolve(error);

    } else if(error && error.response && error.response.status == 403) {

      if(error.response.data && error.response.data.message == 'access_denied'){

        mabvm.$notify({
          title: 'Access Denied',
          message: 'Insufficient Permissions',
          type: 'danger',
          verticalAlign: 'top',
          horizontalAlign: 'right'
        });
      }
      return Promise.resolve(error);

    } else {
      console.log(error);
    }
    return Promise.reject(error);
});

router.beforeEach((to, from, next) => {
  if(to.name == 'login' || to.name == 'logout'){
    // always go to login or logout, no matter what
    next()
  } else {
    if(store.getters.getUser === null){
      // not logged in
      next({ name: 'login' });

    } else {
      // logged in
      
      if(to.meta.module == 'Users' && to.params['email'] == store.getters.getUser.email){
        // profile
        next()
      
      } else if(store.getters.userCan('View', to.meta.module)){
        // any other view (has access)
        next()
      } else {
        // access denied
        if(from.name == 'login'){
          // default to profile
          next({ name: 'users_manage_edit', params: { email: store.getters.getUser.email } })
        } else {
          // deny
          next(false);
        }
      }
    }
  }
});

// force logout on rebuild
router.onError(error => {
  console.log(error);
  if (/Loading .* chunk .* failed/i.test(error.message)) {
    store.dispatch('do_logout', {});
  }
});

// External redirect
if(mabRedirect){
  if(store.getters.getUser && store.getters.getUser.token){
    Vue.prototype.$axios.defaults.headers['Authorization'] = "Bearer " + store.getters.getUser.token;
    router.replace({ path: mabRedirect });
  } else {
    router.replace({ name: 'login' });
  }
}