<template>
  <div :class="'wrapper ' + $route.name">
    <notifications></notifications>
    <div class='logout' v-show="$store.state.user_obj === null">
      <div class='box noselect'>
        <img class='noselect' src='/img/exe/logo_green.svg'/>
        <h2 class='logout_text noselect'>Signing out...</h2>
      </div>
    </div>
    <side-bar :logo="$store.getters.getLogo">
      <template slot="links">
        <sidebar-item
          :link="{ 
            name: $store.state.user_obj ? truncateString($store.state.user_obj.email) : '',
            icon: 'ni ni-circle-08 text-muted text-',
            path: '/users_manage/edit/' + ($store.state.user_obj ? $store.state.user_obj.email : ''),
            isRoute: true
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Map')"
          :link="{
            name: 'Map',
            icon: 'ni ni-pin-3 text-green',
            path: '/map'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Dashboard')"
          :link="{
            name: 'Dashboard',
            icon: 'ni ni-chart-pie-35 text-green',
            path: '/dashboard'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Field Management')"
          :link="{
            name: 'Field Management',
            icon: 'ni ni-square-pin text-green',
            path: '/field_management'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Soil Moisture')"
          :link="{
            name: 'Soil Moisture',
            icon: 'ni ni-atom text-green',
            path: '/soil_moisture'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Nutrients')"
          :link="{
            name: 'Nutrients',
            icon: 'ni ni-atom text-green',
            path: '/nutrients'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Well Controls')"
          :link="{
            name: 'Well Controls',
            icon: 'ni ni-archive-2 text-green',
            path: '/well_controls'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Meters')"
          :link="{
            name: 'Meters',
            icon: 'ni ni-archive-2 text-green',
            path: '/meters'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Node Config')"
          :link="{
            name: 'Node Config',
            icon: 'ni ni-settings text-green',
            path: '/node_config'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Sensor Types')"
          :link="{
            name: 'Sensor Types',
            icon: 'ni ni-settings-gear-65 text-green',
            path: '/sensor_types'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Users')"
          :link="{
            name: 'Users',
            icon: 'ni ni-circle-08 text-green',
            path: '/users_manage'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Entities')"
          :link="{
            name: 'Entities',
            icon: 'ni ni-building text-green',
            path: '/entities_manage'
          }">
        </sidebar-item>
        <sidebar-item v-if="userCan('View', 'Roles')"
          :link="{
            name: 'Roles & Security',
            icon: 'ni ni-badge text-green',
            path: '/roles_manage'
          }">
        </sidebar-item>
        <sidebar-item v-if="isAdmin('fritzbester@gmail.com')"
          :link="{
            name: 'Connections',
            icon: 'ni ni-sound-wave text-green',
            path: '/connections'
          }">
        </sidebar-item>
        <sidebar-item v-if="isAdmin()"
          :link="{
            name: 'Activity Logs',
            icon: 'ni ni-bullet-list-67 text-green',
            path: '/activity_logs'
          }">
        </sidebar-item>
        <sidebar-item
          :link="{
            name: 'Logout',
            icon: 'ni ni-button-power text-green',
            path: '/logout'
          }">
        </sidebar-item>
      </template>
    </side-bar>
    <div class="main-content">
      <div @click="$sidebar.displaySidebar(false)" class='main-content-wrap'>
        <fade-transition :duration="200" origin="center top" mode="out-in">
          <router-view></router-view>
        </fade-transition>
      </div>

      <div class="custom-nav-toggler" @click="toggleSidebar">
          <div class="custom-toggler-line"></div>
          <div class="custom-toggler-line"></div>
          <div class="custom-toggler-line"></div>
      </div>

      <b-button
        v-if="$route.name != 'map' && $route.name != 'field_management'"
        variant="primary"
        size="sm"
        class='support_button' @click="openTicketModal">
        Support
      </b-button>

      <!-- Support Ticket Modal -->
      <b-modal v-model="showTicketModal" v-if="$route.name != 'map' && $route.name != 'field_management'" centered no-close-on-esc no-close-on-backdrop>
        <template #modal-header="{ close }">
          <h6 slot="header" class="modal-title" id="modal-title-default">Log a Support Ticket</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref="supportform" slim>
            <form role="form" autocomplete="off" @submit.prevent="()=>false">
              <b-row>
                <b-col md>
                  <base-input label="Description">
                    <textarea
                      v-model="ticketDescription"
                      placeholder="Please provide a detailed description of the issue(s) you are experiencing.."
                      class="form-control"  rows="10">
                      </textarea>
                  </base-input>
                </b-col>
              </b-row>
              <b-row>
                <b-col md>
                  <validation-provider v-slot="{ errors, validate }">
                    <base-input label="Attachment (optional)" name="attachment" vid="attachment">
                      <b-form-file
                        v-model="ticketAttachment"
                        @change="fileSelectionChange(validate)"
                        placeholder="Select or drop file here.."
                        drop-placeholder="Drop file here...">
                      </b-form-file>
                    </base-input>
                  </validation-provider>
                </b-col>
              </b-row>
            </form>
          </validation-observer>
        </template>
        
        <template #modal-footer="{ ok, cancel, hide }">
          <base-button type="outline-primary" class="ml-auto" @click="closeTicketModal">Cancel</base-button>
          <base-button type="primary" @click="logTicket">Submit</base-button>
        </template>
      </b-modal>

    </div>
  </div>
</template>
<script>
/* eslint-disable no-new */
import PerfectScrollbar from 'perfect-scrollbar';
import 'perfect-scrollbar/css/perfect-scrollbar.css';
import { FadeTransition } from 'vue2-transitions';
import mab_utils from '../../util/mab-utils';

function hasElement(className) {
  return document.getElementsByClassName(className).length > 0;
}

function initScrollbar(className) {
  if (hasElement(className)) {
    new PerfectScrollbar(`.${className}`);
  } else {
    // try to init it later in case this component is loaded async
    setTimeout(() => {
      initScrollbar(className);
    }, 100);
  }
}

export default {

  mixins: [ mab_utils ],

  components: {
    FadeTransition
  },

  data(){
    return {
      type: 'light', // 'Look of the dashboard navbar. Default (Green) or light (gray)'
      showTicketModal: false,
      ticketDescription: '',
      ticketAttachment: '',
    }
  },
  methods: {
    initScrollbar() {
      initScrollbar('sidenav');
    },
    toggleSidebar() {
      this.$sidebar.displaySidebar(!this.$sidebar.showSidebar);
    },
    hideSidebar() {
      this.$sidebar.displaySidebar(false);
    },
    openTicketModal() {
      this.showTicketModal = true;
    },
    closeTicketModal() {
      this.showTicketModal = false;
      this.ticketDescription = '';
      this.ticketAttachment = null;
    },
    fileSelectionChange(validate_func)
    {
      validate_func();
    },
    logTicket() {
      this.$refs.supportform.validate().then(success => {

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

        let fd = new FormData();
        fd.set('message', this.ticketDescription );
        fd.set('email', this.$store.state.user_obj.email );
        fd.set('name', this.$store.state.user_obj.name );
        fd.set('screen', this.$route.name );
        if(this.ticketAttachment){
          fd.append('attachment', this.ticketAttachment );
        }

        this.$axios.post("/api/logticket", fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(resp => {
          if(resp.data.status == 'OK'){
            this.$notify({
              title: 'Success',
              message: 'Support Ticket #' + resp.data.ticketId  + ' Logged',
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.closeTicketModal();
          } else {
            this.$notify({
              title: 'Failed',
              message: 'Failed to log ticket',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        });

      });
    },
    doLogout() {
      this.$store.dispatch('do_logout', {});
    }
  },
  mounted() {
    this.initScrollbar()
  }
};
</script>
<style>

  .form-control.is-invalid {
    border: 1px solid #dee2e6 !important;
  }

  .form-group .invalid-feedback {
    margin:0;
    position:absolute;
  }

  .mapboxgl-popup {
    max-width:100% !important;
  }
  /* Tweaks */
  .mapboxgl-popup-content .mapboxgl-popup-close-button {
    outline:none !important;
    font-size:0.9rem;
    color:white;
    padding:0 0.2em;
    line-height:1;
  }

  /* Map Marker Dialog Box */
  .mapboxgl-popup-content {
    padding:0.75rem;
    /*background:rgba(0,0,0,0.75);*/
    background:linear-gradient(rgba(11, 65, 0, 0.9), rgba(0, 0, 0, 0.75));
    outline:2px solid black;
  }

  .mapboxgl-popup-anchor-bottom .mapboxgl-popup-tip {
    /*border-top-color:rgba(0,0,0,0.75);*/
    border-top-color:black;
  }

  .custom-nav-toggler {
    position:absolute;
    width:2rem;
    height:2rem;
    top:1.5rem;  /* For other pages */
    right:15px;  /* For other pages */
    cursor: pointer;
    flex-flow:column;
    justify-content: center;
    align-items: center;
    display:none;
    background-color:rgba(255,255,255,0.75);
    transition: all .25s ease-in-out;
  }

  .custom-nav-toggler:hover {
    background-color:rgba(255,255,255,1);
  }

  .field_management .custom-nav-toggler,
  .map .custom-nav-toggler {
    top:0.1rem;  /* For the map page */
    left:0;      /* For the map page */
  }

  @media(max-width:1199px){
    .custom-nav-toggler {
      display:flex;
      border-radius:0.25em;
    }
  }

  .custom-nav-toggler, .custom-nav-toggler * {
    box-sizing:border-box;
  }

  .custom-nav-toggler .custom-toggler-line {
    display:block;
    width:1rem;
    height:2px;
    margin-bottom:3px;
    background:#123E1D;
  }

  .focusmenu_expanded .field_management .custom-nav-toggler,
  .focusmenu_expanded .map .custom-nav-toggler {
    display:none;
  }

  .header-body {
    position:relative;
  }

  /* credits: https://codepen.io/claviska */
  .mab_spinner {
    /* Spinner size and color */
    border-top-color: #444;
    border-left-color: #444;

    /* medium by default */
    width: 1rem;
    height: 1rem;

    /* Additional spinner styles */
    animation: mab_spinner 400ms linear infinite;
    border-bottom-color: transparent;
    border-right-color: transparent;
    border-style: solid;
    border-width: 2px;
    border-radius: 50%;
    box-sizing: border-box;
    display: inline-block;
    vertical-align: middle;
  }

  .mab_spinner.small {
    width: 0.8rem !important;
    height: 0.8rem !important;
  }
  
  .mab_spinner.large {
    width: 2rem !important;
    height: 2rem !important;
  }

  /* Animation styles */
  @keyframes mab_spinner {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .mab_spinner.center {
    position:absolute;
    top:50%;
    left:50%;
    display:block;
    transform: translate(-50%,-50%);
  }

  .mab_spinner.right {
    position:absolute;
    top:0;
    right:1rem;
  }

  .mab_spinner.light {
    border-top-color: #fff;
    border-left-color: #fff;
  }

  .mab_spinner.dark {
    border-top-color: #444;
    border-left-color: #444;
  }

  .container-fluid .row.nomargin {
    margin-left:0 !important;
    margin-right:0 !important;
  }

  table .halign {
    text-align:center;
  }

  table tr td.valign {
    vertical-align:middle;
  }

  .card .table th {
    padding: 0.4rem;
  }
  
  .card .table td {
    padding: 0.4rem;
    white-space:normal;
  }

  .hazard {
    border: 10px solid pink;
    border-image: repeating-linear-gradient(
      -45deg,
      #000,
      #000 10px,
      #ffb101 10px,
      #ffb101 20px
    ) 10;
    display:inline-block;
    background:black;
    color:white;
    font-weight:bold;
    font-family:'Courier New', serif;
    text-transform:uppercase;
    text-align:center;
  }

  .el-select .el-select__tags .el-tag {
    background: #00A04C;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .flatpickr-input[readonly] {
    background-color:white;
  }

  .logout {
    position:fixed;
    width:100%;
    height:100%;
    left:0;
    top:0;
    right:0;
    bottom:0;
    background:#fff;
    text-align:center;
    z-index:99999;
  }

  .logout > .box {
    position:absolute;
    top:50%;
    left:50%;
    width:50%;
    height:auto;
    transform:translate(-50%, -50%);
    text-align:center;
  }

  .logout > .box > img {
    width:100%;
    max-width:300px;
    height:auto;
  }

  .logout > .box > .logout_text {
    margin-top:1em;
    font-size:1.5rem;
    color: #00A04C;
  }

  .noselect {
    -webkit-touch-callout: none; /* iOS Safari */
      -webkit-user-select: none; /* Safari */
      -khtml-user-select: none; /* Konqueror HTML */
        -moz-user-select: none; /* Old versions of Firefox */
          -ms-user-select: none; /* Internet Explorer/Edge */
              user-select: none; /* Non-prefixed version, currently
                                    supported by Chrome, Edge, Opera and Firefox */
  }

  .el-steps {
    margin-bottom:1.5em !important;
  }
  .el-step__title {
    line-height:1.1 !important;
    margin-top:0.25em !important;
    font-size:0.8em !important;
  }  

  .wswrap {
    white-space: normal !important;
  }

  .el-input.is-disabled .el-input__inner {
    color:black !important;
  }

  .navbar-nav li .nav-link-text {
    font-size:.8rem;
  }

  @media (min-width: 768px){
    .custom-nav-toggler {
      right:30px;
    }
  }

  /* Map Legend Styling */

  .legend_modal .legend_box .line {
    position:relative;
    display:flex;
    flex-flow:row;
    align-items:center;
    margin-bottom:0.25rem;
  }

  .legend_modal .modal-content {
    background: linear-gradient(rgba(11,65,0,0.9), rgba(0,0,0,0.75));
    outline: 2px solid black !important;
    color: white;
  }

  .legend_modal .modal-title, 
  .legend_modal .modal-content h3,
  .legend_modal .modal-content h6 {
    color:white;
  }

  .legend_modal .modal-content h3,
  .legend_modal .modal-content h6 {
    margin:0.5rem 0;
  }

  .legend_modal .modal-body {
    padding: 0 1.5em;
  }

  .fullwidth {
    width: 100% !important;
  }

  .support_button {
    position:absolute;
    top:2em;
    right:6em;
  }

  .mab_modal .modal-header {
    display: block !important;
  }

  /* Table styling for repeaters */

  .mab_table {
    margin-bottom: 0;
  }

  .mab_table .mab_table_cell {
    vertical-align: middle;
    overflow: hidden;
    white-space: nowrap;
    border:none;
    padding:0.25rem;
  }

  .mab_table .mab_table_cell fieldset {
    margin-bottom: 0;
  }

  .alert-success {
    background-color: #00A04C;
    border-color: #00A04C;
  }

</style>
