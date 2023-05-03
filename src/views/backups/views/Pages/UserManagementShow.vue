<template>
  <div class="content">
    <base-header class="pb-6" type="users">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show="model.name">User - {{model.name}}</h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>
        <div class='row'>
        <div class='col-md-12'>

          <card body-classes="px-0 py-0">
            <template slot="header">
              <h3 class="mb-0">User Details</h3>
            </template>
            <div class="card-body">

              <b-list-group>
                <b-list-group-item><strong>Name</strong>: {{ model.name }} </b-list-group-item>
                <b-list-group-item><strong>Email:</strong> {{ model.email }} </b-list-group-item>
                <b-list-group-item><strong>Mobile:</strong> {{ model.mobile }} </b-list-group-item>
                <b-list-group-item><strong>Address:</strong> {{ model.address }} </b-list-group-item>
                <b-list-group-item><strong>Unit of Measure:</strong> {{ units[model.unit_of_measure-1] }} </b-list-group-item>
                <b-list-group-item><strong>Timezone:</strong> {{ timeZones.filter(x => x._key == model.timezone).map(x => x._val )[0] }} </b-list-group-item>
                <b-list-group-item><strong>Role:</strong> {{ model.role_name }} </b-list-group-item>
                <b-list-group-item><strong>Entity:</strong> {{ model.company_name }} </b-list-group-item>
              </b-list-group>

            </div>
          </card>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Select, Option } from 'element-ui'
import mab_utils from '../../util/mab-utils';

export default {

  mixins: [ mab_utils ],

  components: {
    [Select.name]: Select,
    [Option.name]: Option
  },
  
  data() {
    return {
      timeZones:[],
      units: [
        'Metric',
        'Imperial'
      ],
      model: {
        name: '',
        email: '',
        mobile: '',
        address: '',
        unit_of_measure: '',
        timezone: '',
        role_name: '',
        company_name: ''
      }
    };
  },
  methods: {
    loadUser() {
      this.$axios.get("/api/user/" + this.$route.params.email)
      .then(response => {
        this.model = response.data.user;
      }); 
    },
    loadTimezones() {
      this.$axios.get("/api/getTimezones")
      .then(response => {
        let _timezones = JSON.parse(JSON.stringify(response.data.timezones));
        if(_timezones && _timezones.length){
          for(var i = 0; i < _timezones.length; i++){
            this.timeZones.push({
              '_key': i.toString(), 
              '_val': _timezones[i]
            });
          }
        }
      });
    },
  },
  mounted() {
      this.loadUser();
      this.loadTimezones();
  }
};
</script>
<style></style>
