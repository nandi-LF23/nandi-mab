<template>
  <div class="content">
    <base-header class="pb-6" type="nutrients">
      <b-row align-v="center" class="py-4">
        <b-col>
          <h6 class="h2 text-white d-inline-block mb-0 mr-5" v-show='field_name'>Nutrients - {{ field_name }} - {{
            model.node_address
          }} </h6>
          <div v-show='loading' class='mab_spinner light right'></div>
        </b-col>
      </b-row>
    </base-header>

    <div class='container-fluid mt--6'>

      <!-- Browse Nutrient Templates -->
      <!-- modal that opens when you click on nutrient templlates button. It opens a modal with 3 tabs - Micro Nutrients, Macro Nutrients, Sensor setup -->
      <b-modal ref="my-modal" v-model="browseTemplatesModalVisible" centered no-close-on-esc no-close-on-backdrop
        size='lg'>
        <template #modal-header="{ close }">
          <h6 class="modal-title">Nutrient Templates</h6>
        </template>

        <b-card no-body>
          <b-tabs card>
            <!-- the micro nutrients tab (table showing the templates in a table with apply, edit and remove buttons) -->
            <b-tab title="Calibrations" active>
              <template #default="{ hide }">

                <b-row>
                  <b-col>
                    <b-form-select v-model="perPage">
                      <b-form-select-option :value="5">5</b-form-select-option>
                      <b-form-select-option :value="10" selected>10</b-form-select-option>
                      <b-form-select-option :value="25">25</b-form-select-option>
                      <b-form-select-option :value="50">50</b-form-select-option>
                    </b-form-select>
                  </b-col>
                  <b-col md></b-col>
                  <b-col>
                    <base-input v-model="filterText" prepend-icon="fas fa-search" placeholder="Search...">
                    </base-input>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col md>
                    <b-table striped bordered outlined small stacked="md" responsive show-empty primary-key="id"
                      :per-page="perPage" :current-page="currentPage" :filter="filterText" :fields="templateTableColumns"
                      :items="loadedTemplates" :busy.sync="loading">

                      <template #cell()="data">{{ data.value }}</template>

                      <template #cell(actions)="data">
                        <div class="d-flex justify-content-center">
                          <b-button
                            :disabled="!userCan('Edit', 'Nutrient Templates', data.item.id, 'O') || data.item.name == 'Default Template' || loading"
                            @click="applyNutrientTemplate(data.index, data.item)" class="btn" variant="outline-primary"
                            size="sm" icon>
                            Apply
                          </b-button>
                          <b-button
                            :disabled="!userCan('Edit', 'Nutrient Templates', data.item.id, 'O') || data.item.name == 'Default Template' || loading"
                            @click="editNutrientTemplate(data.index, data.item)" class="btn" variant="outline-primary"
                            size="sm" icon>
                            Edit
                          </b-button>
                          <b-button
                            :disabled="!userCan('Delete', 'Nutrient Templates', data.item.id, 'O') || data.item.name == 'Default Template' || loading"
                            @click="removeNutrientTemplate(data.index, data.item)" class="btn" variant="outline-primary"
                            size="sm" icon>
                            Remove
                          </b-button>
                        </div>
                      </template>

                      <template #table-busy>
                        <div class="text-center">
                          <div class='mab_spinner'></div>
                        </div>
                      </template>

                      <template #empty="scope">
                        <div class="text-center">
                          No templates found
                        </div>
                      </template>

                    </b-table>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col md>
                    Showing {{ Math.min(1 + (perPage * (currentPage - 1)), totalRows) }} to {{
                      Math.min((perPage * (currentPage - 1)) + perPage, totalRows)
                    }} of {{ totalRows }} templates
                  </b-col>
                  <b-col md>
                    <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                      align="right"></b-pagination>
                  </b-col>
                </b-row>

              </template>

            </b-tab>
            <!-- end of micro nutrient tab -->

            <!-- the macro nutrients tab -->
            <b-tab title="Macro Nutrients">
              <!-- show the selected sensors with types from sensor setup tab and get averages from them  -->
              <template #default="{ hide }">
                <b-row>
                  <b-col>
                    <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                      {{ ppm.M3_1 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_2 }}
                    </b-toast>
                    <b-toast v-else-if="toggle = 2" :visible="true" title="NO3(4''/100mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_2 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(4''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_3 }}
                    </b-toast>
                    <b-toast v-else-if="toggle = 2" :visible="true" title="NO3(4''/100mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_3 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(16''/400mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_4 }}
                    </b-toast>
                    <b-toast v-else-if="toggle = 2" :visible="true" title="NO3(4''/100mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M3_4 }}
                    </b-toast>
                  </b-col>

                  <b-col class="horiz-avg">
                    <b-toast v-if="toggle == 2" :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                      no-close-button>
                      <span style="color:#000;">{{ horizontal1_avg }}</span>
                    </b-toast>
                    <b-toast v-else-if="toggle == 1" :visible="false" title="Average" static no-auto-hide no-close-button>
                      &nbsp;
                    </b-toast>
                  </b-col>
                </b-row>

                <b-row>
                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(4''/100mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_1 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_1 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_2 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_2 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_3 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_3 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(16''/400mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_4 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M4_4 }}
                    </b-toast>
                  </b-col>
                  <b-col class="horiz-avg">
                    <b-toast v-if="toggle == 2" :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                      no-close-button>
                      <span style="color:#000;">{{ horizontal2_avg }}</span>
                    </b-toast>
                    <b-toast v-else-if="toggle == 1" :visible="false" title="Average" static no-auto-hide no-close-button>
                      &nbsp;
                    </b-toast>
                  </b-col>
                </b-row>
                <b-row>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(4''/100mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_1 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NO3(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_1 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_2 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="N03(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_2 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_3 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NO3(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_3 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(16''/400mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_4 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M5_4 }}
                    </b-toast>
                  </b-col>
                  <b-col class="horiz-avg">
                    <b-toast v-if="toggle == 2" :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                      no-close-button>
                      <span style="color:#000;">{{ horizontal3_avg }}</span>
                    </b-toast>
                    <b-toast v-else-if="toggle == 1" :visible="false" title="Average" static no-auto-hide no-close-button>
                      &nbsp;
                    </b-toast>
                  </b-col>
                </b-row>
                <b-row>
                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(4''/100mm)  " static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_1 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(16''/400mm)  " static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_1 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(8''/200mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_2 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(16''/400mm)  " static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_2 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NO3(12''/300mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_3 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(16''/400mm)  " static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_3 }}
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast v-if="toggle == 1" :visible="true" title="NH4(16''/400mm)" static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_4 }}
                    </b-toast>
                    <b-toast v-else-if="toggle == 2" :visible="true" title="NH4(16''/400mm)  " static no-auto-hide
                      no-close-button>
                      {{ ppm.M6_4 }}
                    </b-toast>
                  </b-col>
                  <b-col class="horiz-avg">
                    <b-toast v-if="toggle == 2" :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                      no-close-button>
                      <span style="color:#000;">{{ horizontal4_avg }}</span>
                    </b-toast>
                    <b-toast v-else-if="toggle == 1" :visible="false" title="Average" static no-auto-hide no-close-button>
                      &nbsp;
                    </b-toast>
                  </b-col>
                </b-row>

                <b-row v-if="toggle == 1" class="vert-avg">
                  <b-col>
                    <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide no-close-button>
                      <span style="color:#000;">{{ col1_avg }}</span>
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide no-close-button>
                      <span style="color:#000;">{{ col2_avg }}</span>
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide no-close-button>
                      <span style="color:#000;">{{ col3_avg }}</span>
                    </b-toast>
                  </b-col>

                  <b-col>
                    <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide no-close-button>
                      <span style="color:#000;">{{ col4_avg }}</span>
                    </b-toast>
                  </b-col>
                  <b-col>
                    <b-toast :visible="false" title="" static no-auto-hide no-close-button>
                      &nbsp;
                    </b-toast>
                  </b-col>
                </b-row>
              </template>
            </b-tab>
            <!-- end of macro nutrients -->

            <!-- the sensor setup tab, where all the nutrient types are listed with a dropdown of the templates found in the micro nutrients table -->
            <b-tab title="Sensor Setup">
              <template #default="{ hide }">
                <b-row>
                  <b-col md="3" class="p-0">
                    <b-card no-body class="mb-1">
                      <b-card-body class="p-0">
                        <b-list-group>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M3.1</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M3_1">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M4.1</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M4_1">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M5.1</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M5_1">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M6.1</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M6_1">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                        </b-list-group>
                      </b-card-body>
                    </b-card>

                  </b-col>
                  <b-col md="3" class="p-0">
                    <b-card no-body class="mb-1">
                      <b-card-body class="p-0">
                        <b-list-group>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M3.2</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M3_2">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M4.2</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M4_2">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M5.2</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M5_2">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M6.2</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M6_2">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                        </b-list-group>
                      </b-card-body>
                    </b-card>
                  </b-col>

                  <b-col md="3" class="p-0">
                    <b-card no-body class="mb-1">
                      <b-card-body class="p-0">
                        <b-list-group>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M3.3</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M3_3">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M4.3</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M4_3">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M5.3</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M5_3">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M6.3</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M6_3">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                        </b-list-group>
                      </b-card-body>
                    </b-card>
                  </b-col>

                  <b-col md="3" class="p-0">
                    <b-card no-body class="mb-1">
                      <b-card-body class="p-0">
                        <b-list-group>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M3.4</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M3_4">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M4.4</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M4_4">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M5.4</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M5_4">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                          <b-list-group-item class="p-0 pl-2"><span class="sensor_type_heading">M6.4</span>
                            <b-form-select @change.native="saveSensorSetup" class="sensor_select"
                              v-model="nutrient_types.M6_4">
                              <b-form-select-option v-for="item in loadedTemplates" v-bind:key="item.id"
                                :value="item.id">{{
                                  item.name
                                }}</b-form-select-option>
                            </b-form-select>
                          </b-list-group-item>
                        </b-list-group>
                      </b-card-body>
                    </b-card>
                  </b-col>
                </b-row>
                <b-row>
                  <b-col class='text-center'>
                    <div class="d-flex flow-row justify-content-center">
                      <div class="m-1">
                        <!-- 1 is vertical 2 is horizontal -->
                        Presently set to:
                        <p v-if="toggle == 1">Vertical<br /><br /></p>
                        <p v-if="toggle == 2">Horizontal<br /><br /></p>
                        Click here to toggle from Vertical to Horizontal, and vice-versa:<br />
                        <b-button @click="makeVertical" class="ml-auto">Make Vertical</b-button>
                        <b-button @click="makeHorizontal" class="ml-auto">Make Horizontal</b-button>
                      </div>

                    </div>
                  </b-col>
                </b-row>
              </template>
            </b-tab>
            <!-- end of sensor setup tab  -->
          </b-tabs>
        </b-card>

        <!-- the modal footer with add template and close buttons -->
        <template #modal-footer="{ ok, cancel, hide }">
          <b-button :disabled="!userCan('Add', 'Nutrient Templates') || loading" variant="outline-primary" class="ml-auto"
            @click="addNutrientTemplate">
            Add Calibration
          </b-button>
          <b-button variant="outline-primary" @click="saveSensorSetup(); closeBrowseTemplatesModal(); ">
            Close
          </b-button>
        </template>
        <!-- end of modal footer buttons area -->
      </b-modal>
      <!-- end of modal with tabs and adding of nutrients  -->

      <!-- modal when clicking "Add Template" or Edit on the Nutrient template modal. (thus the modal inside the modal under micro nutrients) -->
      <b-modal v-model="manageTemplateModalVisible" centered no-close-on-esc no-close-on-backdrop size='lg'>
        <template #modal-header="{ close }">
          <h6 class="modal-title">{{ currentMode }} Template</h6>
        </template>

        <template #default="{ hide }">
          <validation-observer ref='managetpl' slim>
            <b-row>
              <b-col md>
                <base-input label="Template Name" v-model="template_name" placeholder="Template Name" name="template name"
                  rules="required"></base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input label="Polynomial 1" v-model="template_model.poly1" placeholder="Poly1" name="poly1"
                  rules="required|double"></base-input>
              </b-col>
              <b-col md>
                <base-input label="Polynomial 2" v-model="template_model.poly2" placeholder="Poly2" name="poly2"
                  rules="required|double"></base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input label="Lower Limit" v-model="template_model.lower_limit" placeholder="Lower Limit"
                  name="lower limit" rules="required"></base-input>
              </b-col>
              <b-col md>
                <base-input label="Upper Limit" v-model="template_model.upper_limit" placeholder="Upper Limit"
                  name="upper limit" rules="required"></base-input>
              </b-col>
            </b-row>

            <b-row>
              <b-col md>
                <base-input label="Soil Type" vid="soil_type" rules="required" name="soil type">
                  <el-select placeholder="Soil Type" v-model="template_model.soil_type">
                    <el-option v-for="item in soilTypes" :key="item.value" :label="item.label"
                      :value="item.value"></el-option>
                  </el-select>
                </base-input>
              </b-col>
              <b-col md>
                <base-input label="Metric" vid="metric" rules="required" name="metric">
                  <el-select placeholder="Metric" v-model="template_model.metric">
                    <el-option v-for="item in metrics" :key="item.value" :label="item.label"
                      :value="item.value"></el-option>
                  </el-select>
                </base-input>
              </b-col>
              <b-col md>
                <base-input label="Crop Name" v-model="template_model.crop_name" placeholder="Crop Name" name="crop name"
                  rules="required"></base-input>
              </b-col>
            </b-row>

          </validation-observer>
        </template>

        <template #modal-footer="{ ok, cancel, hide }">
          <b-button variant="outline-primary" class="ml-auto" @click="closeManageNutrientTemplateModal">
            Close
          </b-button>
          <b-button variant="outline-primary" @click="saveNutrientTemplate">
            {{ currentMode == 'Edit' ? 'Update' : 'Add' }}
          </b-button>
        </template>
      </b-modal>
      <!-- end of nutrient add/edit template modal under micro nutrients -->


      <!-- top block on page where reading date, active template amd nutrient template button apprear, accordion and graphs areas too -->
      <div class='row'>
        <div class='col-md-12'>
          <card body-classes="px-0 py-0">

            <template slot="header">
              <b-row>
                <b-col>
                  <base-button :disabled="!userCan('Graph', 'Nutrients', model.node_address, 'O') || loading"
                    @click.native="goToGraph()" class="btn" type="primary" size="sm" v-b-tooltip.hover.top
                    title="Navigate to node's graphing screen." icon>
                    Graph
                  </base-button>
                  <!-- <b-button v-b-modal.modal-2 size="sm" variant="primary" class="btn" v-b-tooltip.hover.top
                      title="Read More">
                      <b-icon icon="question-circle-fill" aria-label="Help"></b-icon>
                    </b-button> -->
                </b-col>
              </b-row>
            </template>

            <div class="card-body">
              <b-row>
                <b-col md>
                  <card body-classes="px-1 py-1">
                    <div class="card-body">
                      <b-row>
                        <b-col md align-self="center">
                          <b-toast :visible="true" title="Readings Date" static no-auto-hide no-close-button>
                            {{ latestDateReported }}
                          </b-toast>
                        </b-col>
                        <b-col md align-self="center">
                          <b-toast :visible="true" title="Active Template" static no-auto-hide no-close-button>
                            <span v-for="item in loadedTemplates" :value="item.id">{{ item.name }}<br></span>
                          </b-toast>
                        </b-col>
                        <b-col md align-self="center">
                          <base-button :disabled="!userCan('View', 'Nutrient Templates') || loading"
                            @click.native="openNutrientTemplatesModal()" class="btn" type="primary" icon>
                            Nutrient Templates
                          </base-button>
                        </b-col>
                        <b-col md align-self="center">
                          <b-toast :visible="true" title="Install Depth" static no-auto-hide no-close-button>
                            <validation-observer ref='nform' slim>
                              <base-input vid="install_depth" rules="required" name="install depth">
                                <el-select @change="syncFields" size="small" placeholder="Install Depth"
                                  v-model="model.install_depth">
                                  <el-option v-for="item in install_depths" :key="item.value" :label="item.label"
                                    :value="item.value"></el-option>
                                </el-select>
                              </base-input>
                            </validation-observer>
                          </b-toast>
                        </b-col>
                      </b-row>
                    </div>
                  </card>
                </b-col>
              </b-row>

              <b-row>
                <b-col md>
                  <div class="accordion" role="tablist">
                    <b-card no-body class="mb-1">
                      <b-card-header header-tag="header" class="p-1" role="tab">
                        <b-button block v-b-toggle.accordion-1 variant="text-left"
                          class="light text-left acc-btn">Raw</b-button>
                      </b-card-header>
                      <b-collapse id="accordion-1" visible accordion="my-accordion" role="tabpanel">
                        <b-card-body>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="M3.1" static no-auto-hide no-close-button>
                                {{ raw.M3_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M3.2" static no-auto-hide no-close-button>
                                {{ raw.M3_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M3.3" static no-auto-hide no-close-button>
                                {{ raw.M3_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M3.4" static no-auto-hide no-close-button>
                                {{ raw.M3_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="M4.1" static no-auto-hide no-close-button>
                                {{ raw.M4_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M4.2" static no-auto-hide no-close-button>
                                {{ raw.M4_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M4.3" static no-auto-hide no-close-button>
                                {{ raw.M4_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M4.4" static no-auto-hide no-close-button>
                                {{ raw.M4_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="M5.1" static no-auto-hide no-close-button>
                                {{ raw.M5_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M5.2" static no-auto-hide no-close-button>
                                {{ raw.M5_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M5.3" static no-auto-hide no-close-button>
                                {{ raw.M5_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M5.4" static no-auto-hide no-close-button>
                                {{ raw.M5_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="M6.1" static no-auto-hide no-close-button>
                                {{ raw.M6_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M6.2" static no-auto-hide no-close-button>
                                {{ raw.M6_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M6.3" static no-auto-hide no-close-button>
                                {{ raw.M6_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="M6.4" static no-auto-hide no-close-button>
                                {{ raw.M6_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                        </b-card-body>
                      </b-collapse>
                    </b-card>

                    <b-card no-body class="mb-1" body-classes="px-1 py-1">
                      <b-card-header header-tag="header" class="p-1" role="tab">
                        <b-button block v-b-toggle.accordion-2 variant="text-left"
                          class="light text-left acc-btn">PPM</b-button>
                      </b-card-header>
                      <b-collapse id="accordion-2" accordion="my-accordion" role="tabpanel">
                        <b-card-body>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ ppm.M3_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ ppm.M3_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ ppm.M3_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ ppm.M3_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ ppm.M4_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ ppm.M4_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ ppm.M4_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ ppm.M4_4 }}
                              </b-toast>
                            </b-col>

                          </b-row>
                          <b-row>

                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ ppm.M5_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ ppm.M5_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ ppm.M5_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ ppm.M5_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)  " static no-auto-hide no-close-button>
                                {{ ppm.M6_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ ppm.M6_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ ppm.M6_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ ppm.M6_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>

                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ col1_avg }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ col2_avg }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ col3_avg }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ col4_avg }}</span>
                              </b-toast>
                            </b-col>
                          </b-row>
                        </b-card-body>
                      </b-collapse>
                    </b-card>

                    <b-card no-body class="mb-1">
                      <b-card-header header-tag="header" class="p-1" role="tab">
                        <b-button block v-b-toggle.accordion-3 variant="text-left"
                          class="light text-left acc-btn">Pounds</b-button>
                      </b-card-header>
                      <b-collapse id="accordion-3" accordion="my-accordion" role="tabpanel">
                        <b-card-body>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ pounds.M3_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ pounds.M3_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ pounds.M3_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ pounds.M3_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ pounds.M4_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ pounds.M4_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ pounds.M4_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ pounds.M4_4 }}
                              </b-toast>
                            </b-col>

                          </b-row>
                          <b-row>

                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)" static no-auto-hide no-close-button>
                                {{ pounds.M5_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ pounds.M5_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ pounds.M5_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ pounds.M5_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>
                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="NO3(4''/100mm)  " static no-auto-hide no-close-button>
                                {{ pounds.M6_1 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(8''/200mm)" static no-auto-hide no-close-button>
                                {{ pounds.M6_2 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NO3(12''/300mm)" static no-auto-hide no-close-button>
                                {{ pounds.M6_3 }}
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="NH4(16''/400mm)" static no-auto-hide no-close-button>
                                {{ pounds.M6_4 }}
                              </b-toast>
                            </b-col>
                          </b-row>

                          <b-row>
                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ pounds.avg_M3 }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ pounds.avg_M4 }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ pounds.avg_M5 }}</span>
                              </b-toast>
                            </b-col>

                            <b-col>
                              <b-toast :visible="true" title="Average" style="color:#00a04c" static no-auto-hide
                                no-close-button>
                                <span style="color:#000;">{{ pounds.avg_M6 }}</span>
                              </b-toast>
                            </b-col>
                          </b-row>
                        </b-card-body>
                      </b-collapse>
                    </b-card>
                  </div>
                </b-col>

                <b-col md>

                  <card>
                    <template slot="header">
                      <strong>Nutrient Gauge</strong>
                    </template>
                    <div class="card-body">
                      <b-row>
                        <b-col sm="6">

                          <!-- the gauge. -->
                          <highcharts class="hc" :options="m3ChartOptions" :update-args="chartUpdateArgs" ref="hchart">
                          </highcharts>

                        </b-col>
                        <b-col sm="6">

                          <!-- the gauge. -->
                          <highcharts class="hc" :options="m4ChartOptions" :update-args="chartUpdateArgs" ref="hchart">
                          </highcharts>

                        </b-col>
                      </b-row>
                      <b-row>
                        <b-col sm="6">

                          <!-- the gauge. -->
                          <highcharts class="hc" :options="m5ChartOptions" :update-args="chartUpdateArgs" ref="hchart">
                          </highcharts>

                        </b-col>
                        <b-col sm="6">

                          <!-- the gauge. -->
                          <highcharts class="hc" :options="m6ChartOptions" :update-args="chartUpdateArgs" ref="hchart">
                          </highcharts>

                        </b-col>
                      </b-row>
                    </div>
                  </card>
                </b-col>
              </b-row>
            </div>
          </card>

          <card body-classes="px-0 py-0">

            <template slot="header">
              <h3 class="mb-0">Graph Configuration</h3>
            </template>

            <div class="card-body">
              <validation-observer ref='nform' slim>
                <b-row align-v="center">
                  <b-col md>
                    <base-input label="Graph Type" name="graph type" rules="required" vid="graph_type">
                      <el-select @change="syncFields" v-model="model.graph_type" filterable placeholder="Graph Type">
                        <el-option label="Nutrients (RAW)" value='nutrient'></el-option>
                        <el-option label="Nutrients (depth in inches/mm)" value='nutrient_ppm'></el-option>
                        <el-option label="Nutrients Type Average" value='nutrient_ppm_avg'></el-option>
                      </el-select>
                    </base-input>
                  </b-col>
                  <b-col md>
                    <base-input label="Graph Start Date" name="start date" vid="graph_start_date">
                      <flat-picker slot-scope="{focus, blur}" @on-open="focus" @on-close="blur" @on-change="syncFields"
                        placeholder="Graph Start Date" class="form-control datepicker" v-model="model.graph_start_date">
                      </flat-picker>
                    </base-input>
                  </b-col>
                </b-row>
              </validation-observer>
            </div>
          </card>
        </div>
      </div>
      <!-- end of reading date, accordion and graph areas -->
    </div>
  </div>
</template>

<script>
/* eslint-disable no-new */
import {
  Select,
  Option
} from 'element-ui'
import flatPicker from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import swal from 'sweetalert2';
import mab_utils from '../../util/mab-utils';
import NiftyGauge from '../../custom_components/NiftyGauge';
import VueSpeedometer from "vue-speedometer"
import Highcharts from 'highcharts';
import { raw } from 'body-parser';
import { get } from 'http';

export default {

  mixins: [mab_utils],

  components: {
    [Select.name]: Select,
    [Option.name]: Option,
    flatPicker,
    NiftyGauge,
    VueSpeedometer
  },

  data() {
    return {
      /*  options1: [{ text: 'Group 1', value: 1 }, { text: 'Group 2', value: 2 }],
        options2: [{ text: 'Group 1', value: 1 }, { text: 'Group 2', value: 2 }],
        options3: [{ text: 'Group 1', value: 1 }, { text: 'Group 2', value: 2 }],
        options4: [{ text: 'Group 1', value: 1 }, { text: 'Group 2', value: 2 }],
        mySelected1: 1,
        mySelected2: 2,
        mySelected3: 1,
        mySelected4: 2,*/
      toggle: 1,
      group: 1,
      selected: 1,
      col1_avg: 0,
      col2_avg: 0,
      col3_avg: 0,
      col4_avg: 0,
      gaugeN03_avg: 0,
      gaugeNH4_avg: 0,
      horizontal1_avg: 0,
      horizontal2_avg: 0,
      horizontal3_avg: 0,
      horizontal4_avg: 0,

      temp: 0,
      soil_moist: 0,
      soil_temp: 0,
      soil_temp_avg: '',

      avg_M1: 0,

      temp_uom: '',

      fields: ['N03_1', 'NH4_1', 'N03', 'NH4'],

      tooltip: {
        enabled: true,
      },

      fields: [{
        key: "N03_1",
        label: "",
        class: "text-center"
      },
      {
        key: "NH4_1",
        label: "",
        class: "text-center"
      },
      {
        key: "N03",
        label: "",
        class: "text-center"
      },
      {
        key: "NH4",
        label: "",
        class: "text-center"
      },
      ],

      items: [{
        isActive: true,
        N03_1: '3.1',
        NH4_1: '3.2',
        N03: '3.3',
        NH4: '3.4',
      },
      {
        isActive: true,
        N03_1: '4.1',
        NH4_1: '4.2',
        N03: '4.3',
        NH4: '4.4',
      },
      {
        isActive: true,
        N03_1: '5.1',
        NH4_1: '5.2',
        N03: '5.3',
        NH4: '5.4',
      },
      {
        isActive: true,
        N03_1: '6.1',
        NH4_1: '6.2',
        N03: '6.3',
        NH4: '6.4',
      },
      ],

      initial: true,
      loading: false,
      date_now: '',
      applied_template: '',

      company_id: null,
      field_id: '',
      field_name: '',

      graph_type: '',
      series: [],
      chartUpdateArgs: [true, true, {
        duration: 1000
      }],
      plotOptions: {
        series: {
          states: {
            inactive: {
              opacity: 1
            }
          }
        }
      },
      exporting: {
        enabled: false
      },
      plotLines: [],
      resetButton: {
        text: "Reset",
        enabled: true,
        onclick: (e) => {
          this.resetSeriesData();
        }
      },

      browseTemplatesModalVisible: false,
      manageTemplateModalVisible: false,

      templateTableColumns: [{
        key: 'name',
        label: 'Template',
        sortable: true,
        tdClass: 'valign'
      },
      {
        key: 'created_at',
        label: 'Date Created',
        sortable: true,
        tdClass: 'valign'
      },
      {
        key: 'user_name',
        label: 'Created By',
        sortable: true,
        tdClass: 'valign'
      },
      {
        key: 'actions',
        label: 'Actions'
      }
      ],

      currentMode: 'Add',

      loadedTemplates: [],
      loadedTemplateId: null,
      loadedTemplateName: 'Default Template',
      latestDateReported: '1970-01-01 00:00:00',

      totalRows: 1,
      currentPage: 1,
      perPage: 10,
      filterText: '',

      gauge_value: 0,

      nutrient_types: {
        M3_1: 0,
        M3_2: 0,
        M3_3: 0,
        M3_4: 0,

        M4_1: 0,
        M4_2: 0,
        M4_3: 0,
        M4_4: 0,

        M5_1: 0,
        M5_2: 0,
        M5_3: 0,
        M5_4: 0,

        M6_1: 0,
        M6_2: 0,
        M6_3: 0,
        M6_4: 0,
      },

      nutrient_groups: {
        M3_1: 1,
        M3_2: 2,
        M3_3: 1,
        M3_4: 2,

        M4_1: 1,
        M4_2: 2,
        M4_3: 1,
        M4_4: 1,

        M5_1: 1,
        M5_2: 2,
        M5_3: 1,
        M5_4: 2,

        M6_1: 1,
        M6_2: 2,
        M6_3: 1,
        M6_4: 1,
      },

      raw: {
        M3_1: 0,
        M3_2: 0,
        M3_3: 0,
        M3_4: 0,

        M4_1: 0,
        M4_2: 0,
        M4_3: 0,
        M4_4: 0,

        M5_1: 0,
        M5_2: 0,
        M5_3: 0,
        M5_4: 0,

        M6_1: 0,
        M6_2: 0,
        M6_3: 0,
        M6_4: 0,

        avg_M3: 0,
        avg_M4: 0,
        avg_M5: 0,
        avg_M6: 0,
      },

      //this is needed. But this value is extracted first, and not the second one.
      //check line 629
      ppm: {
        M3_1: 0,
        M3_2: 0,
        M3_3: 0,
        M3_4: 0,

        M4_1: 0,
        M4_2: 0,
        M4_3: 0,
        M4_4: 0,

        M5_1: 0,
        M5_2: 0,
        M5_3: 0,
        M5_4: 0,

        M6_1: 0,
        M6_2: 0,
        M6_3: 0,
        M6_4: 0,

        avg_M3: 0,
        avg_M4: 0,
        avg_M5: 0,
        avg_M6: 0,
      },

      pounds: {
        M3_1: 0,
        M3_2: 0,
        M3_3: 0,
        M3_4: 0,

        M4_1: 0,
        M4_2: 0,
        M4_3: 0,
        M4_4: 0,

        M5_1: 0,
        M5_2: 0,
        M5_3: 0,
        M5_4: 0,

        M6_1: 0,
        M6_2: 0,
        M6_3: 0,
        M6_4: 0,

        avg_M3: 0,
        avg_M4: 0,
        avg_M5: 0,
        avg_M6: 0,
      },

      soil_m: {
        M0_1: 0,
        M0_2: 0,
        M0_3: 0,
        M0_4: 0,
        avg_M0: 0,
      },

      soil_temp: {
        M1_1: 0,
        M1_2: 0,
        M1_3: 0,
        M1_4: 0,
        avg_M1: 0,
      },

      model: {
        node_id: null,
        node_address: null,
        install_depth: 2.4,
        graph_type: 'nutrients',
        graph_start_date: '',
      },

      template_name: null,
      template_model: {
        id: null,
        poly1: 0,
        poly2: 0,
        lower_limit: 0,
        upper_limit: 0,
        metric: '',
        soil_type: '',
        crop_name: ''
      },

      lower_limit: 0,
      upper_limit: 0,
      nutrient_label: '',

      soilTypes: [{
        value: 1,
        label: 'Sand'
      },
      {
        value: 2,
        label: 'Loamy Sand'
      },
      {
        value: 3,
        label: 'Sandy Loam'
      },
      {
        value: 4,
        label: 'Loam'
      },
      {
        value: 5,
        label: 'Silt Loam'
      },
      {
        value: 6,
        label: 'Silt'
      },
      {
        value: 7,
        label: 'Sandy Clay Loam'
      },
      {
        value: 8,
        label: 'Clay Loam'
      },
      {
        value: 9,
        label: 'Silty Clay Loam'
      },
      {
        value: 10,
        label: 'Sandy Clay'
      },
      {
        value: 11,
        label: 'Silty Clay'
      },
      {
        value: 12,
        label: 'Clay'
      }
      ],

      metrics: [{
        value: 'ppm',
        label: 'Parts per million (PPM)'
      },
      {
        value: 'pounds',
        label: 'Pounds'
      }
      ],

      install_depths: [{
        value: 1.8,
        label: '4" - 1.8 Pound Factor'
      },
      {
        value: 2.1,
        label: '6" - 2.1 Pound Factor'
      },
      {
        value: 2.4,
        label: '8" - 2.4 Pound Factor'
      }
      ]

    }
  },

  computed: {

    //     scopeGet:  {
    //       func(){
    //         var x = 5;

    //         if (x == 5) {
    //           var y = 15;
    //           var y = 10;
    //           console.log("Value of X inside the IF block: ", x);
    //           console.log("Value of Y inside the IF block: ", y);
    //         }

    //         console.log("Value of X outside the IF block: ", x);
    //         console.log("Value of Y outside the IF block: ", y);
    //       }

    // }

    //func();

    /*toggle: {
      get() {
        //console.log('in get');
        //vertical is one, horizontal is two.

        //TODO build route for this, api call.

        if (
          (this.nutrient_groups.M3_1 == 1) &&
          (this.nutrient_groups.M4_1 == 1) &&
          (this.nutrient_groups.M5_1 == 1) &&
          (this.nutrient_groups.M6_1 == 1) &&

          (this.nutrient_groups.M3_2 == 2) &&
          (this.nutrient_groups.M4_2 == 2) &&
          (this.nutrient_groups.M5_2 == 2) &&
          (this.nutrient_groups.M6_2 == 2) &&

          (this.nutrient_groups.M3_3 == 1) &&
          (this.nutrient_groups.M4_3 == 1) &&
          (this.nutrient_groups.M5_3 == 1) &&
          (this.nutrient_groups.M6_3 == 1) &&

          (this.nutrient_groups.M3_4 == 2) &&
          (this.nutrient_groups.M4_4 == 2) &&
          (this.nutrient_groups.M5_4 == 2) &&
          (this.nutrient_groups.M6_4 == 2)
        ) {
          return 1;
        }
        else
          if (
            (this.nutrient_groups.M3_1 == 1) &&
            (this.nutrient_groups.M3_2 == 1) &&
            (this.nutrient_groups.M3_3 == 1) &&
            (this.nutrient_groups.M3_4 == 1) &&

            (this.nutrient_groups.M4_1 == 2) &&
            (this.nutrient_groups.M4_2 == 2) &&
            (this.nutrient_groups.M4_3 == 2) &&
            (this.nutrient_groups.M4_4 == 2) &&

            (this.nutrient_groups.M5_1 == 1) &&
            (this.nutrient_groups.M5_2 == 1) &&
            (this.nutrient_groups.M5_3 == 1) &&
            (this.nutrient_groups.M5_4 == 1) &&

            (this.nutrient_groups.M6_1 == 2) &&
            (this.nutrient_groups.M6_2 == 2) &&
            (this.nutrient_groups.M6_3 == 2) &&
            (this.nutrient_groups.M6_4 == 2)
          ) {
            return 2;
          }
      },
    },*/
    avg1: {
      get() {
        return (this.template_model.poly1) + this.template_model.poly2
      },
    },

    selected1: {
      get() {
        return this.mySelected1
      },
    },

    selected2: {
      get() {
        return this.mySelected2
      },
    },

    selected3: {
      get() {
        return this.mySelected3
      },
    },

    selected4: {
      get() {
        return this.mySelected4
      },
    },
    //average
    chartOptions() {
      var ppmavg = parseFloat(this.ppm.avg);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        credits: {
          enabled: false
        },

        title: {
          text: ''
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: null,
          center: ['50%', '75%'],
          size: '110%',
        },

        yAxis: {
          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 20,
            style: {
              fontSize: '14px'
            }
          },
          plotBands: [{
            from: 20,
            to: 50,
            color: '#55BF3B', // green
            thickness: 50
          }, {
            from: -10,
            to: 20,
            color: '#DDDF0D', // yellow
            thickness: 50
          }, {
            from: -50,
            to: -10,
            color: '#DF5353', // red
            thickness: 50
          }]
        },

        series: [{
          name: 'Average',
          data: [ppmavg],
          dataLabels: {
            format: 'N03 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    m3ChartOptions() {
      var m1Val = parseFloat(this.gaugeN03_avg);


      var lowerLimit = parseFloat(this.lower_limit);
      var upperLimit = parseFloat(this.upper_limit);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        title: null,

        credits: {
          enabled: false
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        yAxis: {
          stops: [
            [0.8, 'red'],
            [1.0, 'green']
          ],
          lineWidth: 0,
          tickInterval: 10,
          minorTickInterval: 2.5,
          title: null,
          labels: {
            y: -5,
            style: {
              'color': 'black',
              'fontWeight': 'bold'
            }
          },

          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 20,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [
            {
              from: 30,
              to: 50,
              color: '#f44536', // red
              thickness: 30,
              borderWidth: 0,
            },
            {
              from: 30,
              to: 32,
              color: 'black', // red
              thickness: 30,
              borderWidth: 0,
              // label: {
              //   text: 'Upper'
              // }
            },
            {
              from: 10,
              to: 30,
              color: '#fbe13b', // yellow
              thickness: 30
            },
            {
              from: -10,
              to: 10,
              color: '#83c14b', // green
              thickness: 30
            },
            {
              from: -10,
              to: -30,
              color: '#fbe13b', // yellow
              thickness: 30,
              // label: {
              //   text: 'L',
              //   textAlign: 'center',
              //   x: 122,
              //   y: 16,
              // }
            },
            {
              from: -12,
              to: -14,
              color: 'black',
              thickness: 30,
              // label: {
              //   text: 'Lower',
              // }
            },
            {
              from: -50,
              to: -30,
              color: '#f44536', // red
              thickness: 30
            },
          ]
        },

        series: [{
          name: 'N03',
          data: [m1Val],
          dataLabels: {
            format: 'N03 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    m4ChartOptions() {
      var NH4_gauge = parseFloat(this.gaugeNH4_avg);
      var lowerLimit = parseFloat(this.lower_limit);
      var upperLimit = parseFloat(this.upper_limit);
      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },
        title: null,

        credits: {
          enabled: false
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        // the value axis
        yAxis: {

          lineWidth: 0,
          tickInterval: 10,
          minorTickInterval: 2.5,
          title: null,
          labels: {
            y: -5,
            style: {
              'color': 'black',
              'fontWeight': 'bold'
            }
          },

          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 20,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [
            {
              from: 30,
              to: 50,
              color: '#f44536', // red
              thickness: 30,
              borderWidth: 0,
            },
            {
              from: 10,
              to: 30,
              color: '#fbe13b', // yellow
              thickness: 30
            },
            {
              from: 25,
              to: 27,
              color: 'black', // black
              thickness: 30,
              borderWidth: 0,
              // label: {
              //   text: 'Upper'
              // }
            },
            {
              from: -10,
              to: 10,
              color: '#83c14b', // green
              thickness: 30
            },
            {
              from: -10,
              to: -30,
              color: '#fbe13b', // yellow
              thickness: 30,
              // label: {
              //   text: 'L',
              //   textAlign: 'center',
              //   x: 122,
              //   y: 16,
              // }
            },

            {
              from: -50,
              to: -30,
              color: '#f44536', // red
              thickness: 30
            },
            {
              from: -35,
              to: -37,
              color: 'black',
              thickness: 30,
            },
          ]
        },

        series: [{
          name: 'NH4',
          data: [NH4_gauge],
          dataLabels: {
            format: 'NH4 {y} PPM',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'grey',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    m5ChartOptions() {
      var soil_moisture = parseFloat(this.soil_m);
      var lowerLimit = parseFloat(this.lower_limit);
      var upperLimit = parseFloat(this.upper_limit);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        title: null,

        credits: {
          enabled: false
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        // the value axis
        yAxis: {
          min: 0,
          max: 100,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 15,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: 0,
            to: 20,
            color: '#f44536', // red
            thickness: 30,
            borderWidth: 0,
          },
          {
            from: 20,
            to: 40,
            color: '#fbe13b', // yellow
            thickness: 30
          },
          {
            from: 40,
            to: 60,
            color: '#83c14b', // green
            thickness: 30,
            borderWidth: 0,
          },
          {
            from: 60,
            to: 80,
            color: '#fbe13b', // yellow
            thickness: 30
          },
          {
            from: 80,
            to: 100,
            color: '#f44536', // red
            thickness: 30
          },

          ]
        },

        series: [{
          name: 'Soil Moisture',
          data: [soil_moisture],
          dataLabels: {
            format: 'SM {y}%',
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }

        }],
        exporting: {
          enabled: false
        },
      }
    },

    m6ChartOptions() {

      var smTempuom = this.temp_uom;

      var tt = smTempuom != '0' ? smTempuom : '0';

      var smTemp = parseFloat(this.soil_temp.avg_M1);
      smTemp = parseFloat(smTemp.toFixed(2));
      // console.log(smTemp);

      var lowerLimit = parseFloat(this.lower_limit);
      var upperLimit = parseFloat(this.upper_limit);

      return {
        tooltip: {
          enabled: false
        },
        chart: {
          type: 'gauge',
          plotBackgroundColor: null,
          plotBackgroundImage: null,
          plotBorderWidth: 0,
          plotShadow: false,
          height: '80%'
        },

        title: null,

        credits: {
          enabled: false
        },

        pane: {
          startAngle: -90,
          endAngle: 90,
          background: {
            borderColor: 'transparent',
            backgroundColor: 'transparent',
          },
          center: ['50%', '75%'],
          size: '110%',
        },

        // the value axis
        yAxis: {
          min: -50,
          max: 50,
          tickPixelInterval: 72,
          tickPosition: 'inside',
          tickColor: '#000000',
          tickLength: 20,
          tickWidth: 0,
          minorTickInterval: null,
          labels: {
            distance: 15,
            style: {
              fontSize: '8px'
            }
          },
          plotBands: [{
            from: 30,
            to: 50,
            color: '#f44536', // red
            thickness: 30,
            borderWidth: 0,
          },
          {
            from: 10,
            to: 30,
            color: '#fbe13b', // yellow
            thickness: 30
          },
          {
            from: -10,
            to: 10,
            color: '#83c14b', // green
            thickness: 30
          },
          {
            from: -10,
            to: -30,
            color: '#fbe13b', // yellow
            thickness: 30
          },
          {
            from: -50,
            to: -30,
            color: '#f44536', // red
            thickness: 30
          },
          ]
        },

        series: [{
          name: 'Temp',
          data: [smTemp],
          dataLabels: {
            format: 'Temp {y}' + tt,
            borderWidth: 0,
            color: '#333333',
            style: {
              fontSize: '16px'
            }
          },
          dial: {
            radius: '80%',
            backgroundColor: 'black',
            baseWidth: 12,
            baseLength: '0%',
            rearLength: '0%'
          },
          pivot: {
            backgroundColor: 'gray',
            radius: 6
          }
        }],
        exporting: {
          enabled: false
        },
      }
    },

  },

  methods: {

    showModal() {
      this.$refs['my-modal'].show()
    },
    hideModal() {
      this.$refs['my-modal'].hide()
    },

    makeHorizontal() {

      this.nutrient_types.M3_2 = this.nutrient_types.M3_1;
      this.nutrient_types.M3_3 = this.nutrient_types.M3_1;
      this.nutrient_types.M3_4 = this.nutrient_types.M3_1;

      this.nutrient_types.M4_2 = this.nutrient_types.M4_1;
      this.nutrient_types.M4_3 = this.nutrient_types.M4_1;
      this.nutrient_types.M4_4 = this.nutrient_types.M4_1;

      this.nutrient_types.M5_2 = this.nutrient_types.M5_1;
      this.nutrient_types.M5_3 = this.nutrient_types.M5_1;
      this.nutrient_types.M5_4 = this.nutrient_types.M5_1;

      this.nutrient_types.M6_2 = this.nutrient_types.M6_1;
      this.nutrient_types.M6_3 = this.nutrient_types.M6_1;
      this.nutrient_types.M6_4 = this.nutrient_types.M6_1;

      this.nutrient_groups.M3_1 = 1;
      this.nutrient_groups.M4_1 = 1;
      this.nutrient_groups.M5_1 = 1;
      this.nutrient_groups.M6_1 = 1;

      this.nutrient_groups.M3_2 = 2;
      this.nutrient_groups.M4_2 = 2;
      this.nutrient_groups.M5_2 = 2;
      this.nutrient_groups.M6_2 = 2;

      this.nutrient_groups.M3_3 = 1;
      this.nutrient_groups.M4_3 = 1;
      this.nutrient_groups.M5_3 = 1;
      this.nutrient_groups.M6_3 = 1;

      this.nutrient_groups.M3_4 = 2;
      this.nutrient_groups.M4_4 = 2;
      this.nutrient_groups.M5_4 = 2;
      this.nutrient_groups.M6_4 = 2;
      this.toggle = 2;
      this.saveChangeGroup();
    },
    makeVertical() {

      this.nutrient_types.M4_1 = this.nutrient_types.M3_1;
      this.nutrient_types.M5_1 = this.nutrient_types.M3_1;
      this.nutrient_types.M6_1 = this.nutrient_types.M3_1;

      this.nutrient_types.M4_2 = this.nutrient_types.M3_2;
      this.nutrient_types.M5_2 = this.nutrient_types.M3_2;
      this.nutrient_types.M6_2 = this.nutrient_types.M3_2;

      this.nutrient_types.M4_3 = this.nutrient_types.M3_3;
      this.nutrient_types.M5_3 = this.nutrient_types.M3_3;
      this.nutrient_types.M6_3 = this.nutrient_types.M3_3;

      this.nutrient_types.M4_4 = this.nutrient_types.M3_4;
      this.nutrient_types.M5_4 = this.nutrient_types.M3_4;
      this.nutrient_types.M6_4 = this.nutrient_types.M3_4;

      this.nutrient_groups.M3_1 = 1;
      this.nutrient_groups.M3_2 = 2;
      this.nutrient_groups.M3_3 = 1;
      this.nutrient_groups.M3_4 = 2;

      this.nutrient_groups.M4_1 = 2;
      this.nutrient_groups.M4_2 = 1;
      this.nutrient_groups.M4_3 = 2;
      this.nutrient_groups.M4_4 = 1;

      this.nutrient_groups.M5_1 = 1;
      this.nutrient_groups.M5_2 = 2;
      this.nutrient_groups.M5_3 = 1;
      this.nutrient_groups.M5_4 = 2;

      this.nutrient_groups.M6_1 = 1;
      this.nutrient_groups.M6_2 = 2;
      this.nutrient_groups.M6_3 = 1;
      this.nutrient_groups.M6_4 = 2;



      this.toggle = 1;

      this.saveChangeGroup();
    },
    saveChangeGroup() {

      this.loading = true;
      this.$axios.post("/api/saveNutriTemplateDataGroup", {
        nutrient_groups: this.nutrient_groups,
        nutriprobe: this.$route.params.node_address
      }).then((resp) => {
        this.loading = false;
        //console.log(resp);
      });
    },

    saveSensorSetup() {
      // console.log('saving.');
      this.loading = true;
      this.$axios.post("/api/saveNutriTemplateData", {
        sensor_types: this.nutrient_types,
        nutriprobe: this.$route.params.node_address
      }).then((resp) => {
        this.loading = false;
        // console.log(resp.satus);
      });

      /*
      console.log(event.target.value);
      return event.target.value*/
    },

    loadNutrients() {


      this.loading = true;
      this.gauge_value = 0;
      this.loading = true;

      this.$axios.get("/api/loadNutriTemplateData/" + this.$route.params.node_address)
        .then((resp2) => {
          this.nutrient_types.M3_1 = resp2.data.M3_1;
          this.nutrient_types.M4_1 = resp2.data.M4_1;
          this.nutrient_types.M5_1 = resp2.data.M5_1;
          this.nutrient_types.M6_1 = resp2.data.M6_1;

          this.nutrient_types.M3_2 = resp2.data.M3_2;
          this.nutrient_types.M4_2 = resp2.data.M4_2;
          this.nutrient_types.M5_2 = resp2.data.M5_2;
          this.nutrient_types.M6_2 = resp2.data.M6_2;

          this.nutrient_types.M3_3 = resp2.data.M3_3;
          this.nutrient_types.M4_3 = resp2.data.M4_3;
          this.nutrient_types.M5_3 = resp2.data.M5_3;
          this.nutrient_types.M6_3 = resp2.data.M6_3;

          this.nutrient_types.M3_4 = resp2.data.M3_4;
          this.nutrient_types.M4_4 = resp2.data.M4_4;
          this.nutrient_types.M5_4 = resp2.data.M5_4;
          this.nutrient_types.M6_4 = resp2.data.M6_4;

          this.nutrient_groups.M3_1 = resp2.data.M3_1_GROUP;
          this.nutrient_groups.M3_2 = resp2.data.M3_2_GROUP;
          this.nutrient_groups.M3_3 = resp2.data.M3_3_GROUP;
          this.nutrient_groups.M3_4 = resp2.data.M3_4_GROUP;

          this.nutrient_groups.M4_1 = resp2.data.M4_1_GROUP;
          this.nutrient_groups.M4_2 = resp2.data.M4_2_GROUP;
          this.nutrient_groups.M4_3 = resp2.data.M4_3_GROUP;
          this.nutrient_groups.M4_4 = resp2.data.M4_4_GROUP;

          this.nutrient_groups.M5_1 = resp2.data.M5_1_GROUP;
          this.nutrient_groups.M5_2 = resp2.data.M5_2_GROUP;
          this.nutrient_groups.M5_3 = resp2.data.M5_3_GROUP;
          this.nutrient_groups.M5_4 = resp2.data.M5_4_GROUP;

          this.nutrient_groups.M6_1 = resp2.data.M6_1_GROUP;
          this.nutrient_groups.M6_2 = resp2.data.M6_2_GROUP;
          this.nutrient_groups.M6_3 = resp2.data.M6_3_GROUP;
          this.nutrient_groups.M6_4 = resp2.data.M6_4_GROUP;
          this.loading = false;
        });



      this.$axios.get("/api/ManageNutrients/" + this.$route.params.node_address)
        .then((resp) => {
          this.loading = false;
          if (resp.data) {
            //todo load nutriet groups
            this.company_id = resp.data.company_id;
            this.$axios.get("/api/loadNutrientTemplates/" + this.company_id)
              .then((resp1) => {

                this.loading = false;
                  this.loadedTemplates = resp1.data.templates;
                this.totalRows = this.loadedTemplates.length;
              });


            this.field_id = resp.data.field_id;
            this.field_name = resp.data.field_name;

            this.lower_limit = resp.data.lower_limit;
            this.upper_limit = resp.data.upper_limit;
            this.nutrient_label = resp.data.nutrient_label;

            this.metric = resp.data.metric;
            this.gauge_value = resp.data.gauge_value;

            this.raw = resp.data.raw;
            this.ppm = resp.data.ppm;
            console.log(this.ppm);
            this.pounds = resp.data.pounds;
            this.soil_m = resp.data.soil_moist;
            this.soil_temp = resp.data.temp;
            this.soil_temp_avg = resp.data.temp.avg_M1;
            this.temp_uom = resp.data.temp_uom;
            //console.log(this.temp_uom);

            this.loadedTemplateId = resp.data.active_template_id;
            this.loadedTemplateName = resp.data.active_template_name;
            this.loadedTemplateCurrent = resp.data.current_template;
            this.latestDateReported = resp.data.latest_date_reported;

            if (this.initial) {
              this.model.node_id = resp.data.node_id;
              this.model.node_address = resp.data.node_address;
              this.model.install_depth = resp.data.install_depth;
              this.model.graph_type = resp.data.graph_type;
              this.model.graph_start_date = resp.data.graph_start_date;
            }

            this.date_now = resp.data.date_now;

            let temp_data = JSON.parse(this.loadedTemplateCurrent);
            console.log(temp_data);

            // this.col1_avg = (parseFloat(((this.ppm.M3_1) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M4_1) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M5_1) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M6_1) * (temp_data.poly1)) + (temp_data.poly2)) / 4).to Fixed(2);
            this.col1_avg = (parseFloat(this.ppm.M3_1)) + (parseFloat(this.ppm.M4_1)) + (parseFloat(this.ppm.M5_1)) + (parseFloat(this.ppm.M6_1));
            this.col1_avg = (this.col1_avg / 4).toFixed(1);

            this.col2_avg = (parseFloat(this.ppm.M3_2)) + (parseFloat(this.ppm.M4_2)) + (parseFloat(this.ppm.M5_2)) + (parseFloat(this.ppm.M6_2));
            this.col2_avg = (this.col2_avg / 4).toFixed(1);

            this.col3_avg = (parseFloat(this.ppm.M3_3)) + (parseFloat(this.ppm.M4_3)) + (parseFloat(this.ppm.M5_3)) + (parseFloat(this.ppm.M6_3));
            this.col3_avg = (this.col3_avg / 4).toFixed(1);

            this.col4_avg = (parseFloat(this.ppm.M3_4)) + (parseFloat(this.ppm.M4_4)) + (parseFloat(this.ppm.M5_4)) + (parseFloat(this.ppm.M6_4));
            this.col4_avg = (this.col4_avg / 4).toFixed(1);

            this.col5_avg = (parseFloat(this.ppm.M3_5)) + (parseFloat(this.ppm.M4_5)) + (parseFloat(this.ppm.M5_5)) + (parseFloat(this.ppm.M6_5));
            this.col5_avg = (this.col5_avg / 4).toFixed(1);

            this.col6_avg = (parseFloat(this.ppm.M3_6)) + (parseFloat(this.ppm.M4_6)) + (parseFloat(this.ppm.M5_6)) + (parseFloat(this.ppm.M6_6));
            this.col6_avg = this.col6_avg / 4;

            this.soil_m = (parseFloat(this.soil_m.M0_1)) + (parseFloat(this.soil_m.M0_2)) + (parseFloat(this.soil_m.M0_3)) + (parseFloat(this.soil_m.M0_4));
            this.soil_m = (this.soil_m / 4);
            this.soil_m = this.soil_m;
            this.soil_m = this.soil_m.toFixed(2);

            // this.soil_temp = (parseFloat(this.soil_temp.M1_1)) + (parseFloat(this.soil_temp.M1_2)) + (parseFloat(this.soil_temp.M1_3)) + (parseFloat(this.soil_temp.M1_4));
            // this.soil_temp = (this.soil_temp.avg_M1 / 4).toFixed(1);

            this.horizontal1_avg = (parseFloat(this.ppm.M3_1)) + (parseFloat(this.ppm.M3_2)) + (parseFloat(this.ppm.M3_3)) + (parseFloat(this.ppm.M3_4));
            this.horizontal1_avg = (this.horizontal1_avg / 4).toFixed(1);

            this.horizontal2_avg = (parseFloat(this.ppm.M4_1)) + (parseFloat(this.ppm.M4_2)) + (parseFloat(this.ppm.M4_3)) + (parseFloat(this.ppm.M4_4));
            this.horizontal2_avg = (this.horizontal2_avg / 4).toFixed(1);

            this.horizontal3_avg = (parseFloat(this.ppm.M5_1)) + (parseFloat(this.ppm.M5_2)) + (parseFloat(this.ppm.M5_3)) + (parseFloat(this.ppm.M5_4));
            this.horizontal3_avg = (this.horizontal3_avg / 4).toFixed(1);

            this.horizontal4_avg = (parseFloat(this.ppm.M6_1)) + (parseFloat(this.ppm.M6_2)) + (parseFloat(this.ppm.M6_3)) + (parseFloat(this.ppm.M6_4));
            this.horizontal4_avg = (this.horizontal4_avg / 4).toFixed(1);

            //gauge_averge
            this.gaugeN03_avg = (parseFloat(this.ppm.M3_1)) + (parseFloat(this.ppm.M4_1)) + (parseFloat(this.ppm.M5_1)) + (parseFloat(this.ppm.M6_1)) + (parseFloat(this.ppm.M3_3)) + (parseFloat(this.ppm.M4_3)) + (parseFloat(this.ppm.M5_3)) + (parseFloat(this.ppm.M6_3));
            this.gaugeN03_avg = (this.gaugeN03_avg / 8).toFixed(1);

            this.gaugeNH4_avg = (parseFloat(this.ppm.M3_2)) + (parseFloat(this.ppm.M4_2)) + (parseFloat(this.ppm.M5_2)) + (parseFloat(this.ppm.M6_2)) + (parseFloat(this.ppm.M3_4)) + (parseFloat(this.ppm.M4_4)) + (parseFloat(this.ppm.M5_4)) + (parseFloat(this.ppm.M6_4));
            this.gaugeNH4_avg = (this.gaugeNH4_avg / 8).toFixed(1);




            // this.col2_avg = (parseFloat(((this.ppm.M3_2) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M4_2) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M5_2) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M6_2) * (temp_data.poly1)) + (temp_data.poly2)) / 4);
            // this.col3_avg = (parseFloat(((this.ppm.M3_3) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M4_3) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M5_3) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M6_3) * (temp_data.poly1)) + (temp_data.poly2)) / 4);
            // this.col4_avg = (parseFloat(((this.ppm.M3_4) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M4_4) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M5_4) * (temp_data.poly1)) + (temp_data.poly2)) + parseFloat(((this.ppm.M6_4) * (temp_data.poly1)) + (temp_data.poly2)) / 4);

            // console.log(this.col1_avg);

            // nasty fix for a nasty datepicker
            setTimeout(() => {
              this.initial = false;
            }, 1000);
          }
        });

      //this.loadGraphData();

    },

    /* Load Saved Cultivar Templates */
    loadNutrientTemplates() {

      this.loading = true;
      this.$axios.get("/api/loadNutrientTemplates/" + this.company_id)
        .then((resp) => {

          this.loading = false;
          this.loadedTemplates = resp.data.templates;
          this.totalRows = this.loadedTemplates.length;
        });
    },

    syncFields() {
      if (this.initial) return;

      this.$refs.nform.validate()
        .then(success => {

          if (!success) {
            this.$notify({
              title: 'Required',
              message: 'Please fill in all fields',
              type: 'danger',
              verticalAlign: 'top',
              horizontalAlign: 'right',
              duration: 1000,
              clean: true
            });
            return;
          }

          this.loading = true;
          this.$axios.post("/api/SaveNutrients", {
            model: this.model
          })
            .then((resp) => {
              this.loading = false;
              if (resp.data.message == 'field_updated') {
                this.loadNutrients();
                this.$notify({
                  title: 'Saved',
                  message: 'Changes were saved',
                  type: 'success',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });
                this.$refs.nform.reset();
              }
            });

        });
    },

    addNutrientTemplate() {
      this.currentMode = 'Add';
      this.clearNutrientTemplateModel();
      this.openManageNutrientTemplateModal();
    },

    editNutrientTemplate(index, item) {
      this.currentMode = 'Edit';
      let model = JSON.parse(item.template);
      this.template_name = item.name;
      this.template_model = Object.assign({}, model);
      this.template_model.id = item.id;
      this.openManageNutrientTemplateModal();
    },

    removeNutrientTemplate(index, item) {
      swal.fire({
        title: 'Remove Template?',
        text: 'Template will be permanently deleted',
        showCancelButton: true,
        confirmButtonText: 'Remove Template',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.loading = true;
          this.$axios.post("/api/removeNutrientTemplate", {
            id: item.id
          })
            .then((resp) => {
              this.loading = false;
              if (resp.data.status == 'template_removed') {

                this.loadNutrients();

                /* Update table to reflect deletion */
                let indexToDelete = this.loadedTemplates.findIndex(row => row.id === item.id);
                if (indexToDelete >= 0) {
                  this.loadedTemplates.splice(indexToDelete, 1);
                }

                this.$notify({
                  title: 'Success',
                  message: 'Template Removed',
                  type: 'success',
                  verticalAlign: 'top',
                  horizontalAlign: 'right'
                });

              }
            });
        }
      });
    },

    saveNutrientTemplate() {
      this.$refs.managetpl.validate().then(success => {
        if (!success) {
          return;
        }

        this.loading = true;
        this.$axios.post("/api/saveNutrientTemplate", {
          name: this.template_name,
          template: JSON.stringify(this.template_model),
          new: this.template_model.id === null ? 'yes' : 'no',
          company_id: this.company_id,
          id: this.template_model.id
        }).then((resp) => {
          this.loading = false;
          if (resp.data.status == 'template_saved') {

            // REFRESH TEMPLATES
            this.loadNutrients();
            this.loadNutrientTemplates();
            this.$notify({
              title: 'Success',
              message: 'Template ' + (this.currentMode == 'Add' ? 'Added' : 'Updated'),
              type: 'success',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
            this.closeManageNutrientTemplateModal();

          } else if (resp.data.status == 'template_save_error') {
            this.$notify({
              title: 'Failed',
              message: 'Failed to save template. Contact your admin.',
              type: 'warning',
              verticalAlign: 'top',
              horizontalAlign: 'right'
            });
          }
        });
      });
    },

    applyNutrientTemplate(index, item) {
      swal.fire({
        title: 'Apply Template?',
        text: 'Template will be applied to the current nutrient configuration',
        showCancelButton: true,
        confirmButtonText: 'Apply Template',
        buttonsStyling: false,
        customClass: {
          cancelButton: 'btn btn-outline-primary',
          confirmButton: 'btn btn-primary'
        }
      }).then(result => {
        if (result.value) {
          this.loading = true;
          this.$axios.post("/api/applyNutrientTemplate", {
            id: item.id,
            node_address: this.model.node_address
          }).then((resp) => {
            this.loading = false;
            this.loadedTemplateId = item.id;
            this.loadedTemplateName = item.name;
            if (resp.data.status == 'template_applied') {
              this.closeBrowseTemplatesModal();
              this.loadNutrients();
              this.$notify({
                title: 'Success',
                message: 'Template applied',
                type: 'success',
                verticalAlign: 'top',
                horizontalAlign: 'right'
              });
            }
          });
        }
      });
    },

    clearNutrientTemplateModel() {
      this.currentMode = 'Add';
      this.template_model.id = null;
      this.template_model.poly1 = 0;
      this.template_model.poly2 = 0;
      this.template_model.lower_limit = 0;
      this.template_model.upper_limit = 0;
      this.template_model.metric = '';
      this.template_model.soil_type = '';
      this.template_model.crop_name = '';
      if (typeof this.$refs['managetpl'] !== 'undefined') {
        this.$refs['managetpl'].reset();
      }
    },

    openNutrientTemplatesModal() {
      this.loadNutrientTemplates();
      this.browseTemplatesModalVisible = true;
    },

    openMicroTemplatesModal() {
      // this.loadNutrientTemplates();
      this.browseTemplatesModalVisible = true;
    },

    closeBrowseTemplatesModal() {
      this.browseTemplatesModalVisible = false;
    },

    openManageNutrientTemplateModal() {
      this.manageTemplateModalVisible = true;
    },

    closeManageNutrientTemplateModal() {
      this.clearNutrientTemplateModel();
      this.manageTemplateModalVisible = false;
    },

    goToGraph() {
      this.$router.push({
        name: "nutrients_graph",
        params: {
          node_id: this.model.node_id,
          node_address: this.model.node_address,
          field_id: this.field_id,
          field_name: this.field_name,
          graph_type: this.model.graph_type,
          graph_start_date: this.model.graph_start_date
        }
      });
    },
  },

  //mounted() {
  //  this.loadNutrientTemplates();
  // },

  created() {
    this.loadNutrients();

  },

};
</script>

<style>
.toast-header,
.toast-body {
  font-size: 12px !important;
}

.accordion .card-header {
  padding: 0.6rem 0.5rem !important;
  margin-bottom: 0 !important;
  background-color: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, .05);
}

.acc-btn {
  font-size: 1rem;
}

.sensor_select {
  width: 120px;
  margin-left: 10px;
  border-radius: 0;
  float: right;
}

.sensor_type_heading {
  display: inline-block;
  height: 38px;
  padding-top: 9px;
  padding-left: 6px;
}
</style>
