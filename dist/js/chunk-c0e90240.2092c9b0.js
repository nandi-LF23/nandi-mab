(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-c0e90240"],{"077a":function(e,t,o){"use strict";o("d067")},"0bb7":function(e,t,o){"use strict";o.r(t);var a,n=function(){var e=this,t=e.$createElement,o=e._self._c||t;return o("div",{staticClass:"content"},[o("base-header",{staticClass:"pb-6",attrs:{type:"roles"}},[o("b-row",{staticClass:"py-4",attrs:{"align-v":"center"}},[o("b-col",[o("h6",{staticClass:"h2 text-white d-inline-block mb-0 mr-5"},[e._v("Roles & Security")]),o("div",{directives:[{name:"show",rawName:"v-show",value:e.loading,expression:"loading"}],staticClass:"mab_spinner light right"})])],1)],1),o("b-container",{staticClass:"mt--6",attrs:{fluid:""}},[o("b-modal",{attrs:{centered:"","no-close-on-esc":"","no-close-on-backdrop":""},scopedSlots:e._u([{key:"modal-header",fn:function(t){t.close;return[o("h6",{staticClass:"modal-title",attrs:{slot:"header",id:"modal-title-default"},slot:"header"},[e._v("Create New Role")])]}},{key:"default",fn:function(t){t.hide;return[o("validation-observer",{ref:"form",attrs:{slim:""}},[o("form",{attrs:{role:"form",autocomplete:"off"},on:{submit:function(e){return e.preventDefault(),function(){return!1}.apply(null,arguments)}}},[o("b-row",[o("b-col",{attrs:{md:""}},[o("base-input",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],attrs:{name:"role name",rules:"required",placeholder:"Role Name",vid:"name",title:"Enter a descriptive name for the role."},model:{value:e.model.role_name,callback:function(t){e.$set(e.model,"role_name",t)},expression:"model.role_name"}})],1)],1),o("b-row",[o("b-col",{attrs:{md:""}},[o("base-input",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],attrs:{name:"company",rules:"required",vid:"company",title:"The entity that the role would belong to."}},[o("el-select",{attrs:{filterable:"",placeholder:"Entity",disabled:!e.multipleCompanies},model:{value:e.model.company_id,callback:function(t){e.$set(e.model,"company_id",t)},expression:"model.company_id"}},e._l(e.companies,(function(e){return o("el-option",{key:e.id,attrs:{label:e.company_name,value:e.id}})})),1)],1)],1)],1)],1)])]}},{key:"modal-footer",fn:function(t){t.ok,t.cancel,t.hide;return[o("base-button",{staticClass:"ml-auto",attrs:{type:"outline-primary"},on:{click:function(t){return e.closeCreateRoleModal()}}},[e._v("Cancel")]),o("base-button",{attrs:{type:"primary"},on:{click:function(t){return e.createRole()}}},[e._v("Create")])]}}]),model:{value:e.showNewRoleModal,callback:function(t){e.showNewRoleModal=t},expression:"showNewRoleModal"}}),o("card",{staticClass:"no-border-card",attrs:{"body-classes":"px-0 pb-1","footer-classes":"pb-2"}},[o("template",{slot:"header"},[o("base-button",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],staticClass:"btn",attrs:{disabled:!e.userCan("Add","Roles")||e.loading,size:"sm",type:"primary",title:"Add a new entity role. Roles group users into common security groups.",icon:""},nativeOn:{click:function(t){return e.showcreateRoleModal()}}},[e._v("\n          Add Role\n        ")]),o("base-button",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],staticClass:"btn",attrs:{disabled:!e.userCan("View","Groups")||e.loading,type:"primary",size:"sm",title:"Manage groups. Groups are used to effect permissions on a group scale.",icon:""},nativeOn:{click:function(t){return e.goToGroups()}}},[e._v("\n          Manage Groups\n        ")])],1),o("b-row",{staticClass:"nomargin"},[o("b-col",{attrs:{md:""}},[o("b-form-select",{on:{change:e.loadRoles},model:{value:e.perPage,callback:function(t){e.perPage=t},expression:"perPage"}},[o("b-form-select-option",{attrs:{value:5}},[e._v("5")]),o("b-form-select-option",{attrs:{value:10}},[e._v("10")]),o("b-form-select-option",{attrs:{value:25,selected:""}},[e._v("25")]),o("b-form-select-option",{attrs:{value:50}},[e._v("50")])],1)],1),o("b-col",{attrs:{md:""}},[e.entities&&e.entities.length&&e.entities.length>1?o("el-select",{staticClass:"fullwidth",attrs:{clearable:"",filterable:"",placeholder:"Filter by Entity.."},on:{change:e.loadRoles},model:{value:e.filterEntity,callback:function(t){e.filterEntity=t},expression:"filterEntity"}},e._l(e.entities,(function(e){return o("el-option",{key:e.id,attrs:{value:e.id,label:e.company_name}})})),1):e._e()],1),o("b-col",{attrs:{md:""}},[o("base-input",{attrs:{"prepend-icon":"fas fa-search"}},[o("b-input",{attrs:{debounce:1e3,placeholder:"Search..."},on:{update:e.loadRoles},model:{value:e.filterText,callback:function(t){e.filterText=t},expression:"filterText"}})],1)],1)],1),o("b-row",{staticClass:"nomargin"},[o("b-col",{attrs:{md:""}},[o("b-table",{attrs:{striped:"",bordered:"",outlined:"",small:"",stacked:"lg",responsive:"","show-empty":"","primary-key":"id","no-local-sorting":"",fields:e.tableColumns,items:e.tableData,busy:e.bInitialQuery},on:{"sort-changed":e.sortingChanged,"update:busy":function(t){e.bInitialQuery=t}},scopedSlots:e._u([{key:"cell()",fn:function(t){return[e._v("\n              "+e._s(t.value)+"\n            ")]}},e.canAction?{key:"cell(actions)",fn:function(t){return[o("div",{staticClass:"d-flex justify-content-center"},[o("b-button",{staticClass:"btn",attrs:{disabled:!e.userCan("Edit","Roles",t.item.id,"O"),size:"sm",variant:"outline-primary",icon:""},on:{click:function(o){return e.handleEdit(t.index,t.item)}}},[e._v("\n                Configure\n                ")]),o("b-button",{staticClass:"btn",attrs:{disabled:!e.userCan("Delete","Roles",t.item.id,"O"),size:"sm",variant:"outline-primary",icon:""},on:{click:function(o){return e.handleDelete(t.index,t.item)}}},[e._v("\n                Remove\n                ")])],1)]}}:null,{key:"table-busy",fn:function(){return[o("div",{staticClass:"text-center"},[o("div",{staticClass:"mab_spinner"})])]},proxy:!0},{key:"emptyfiltered",fn:function(){return[o("div",{staticClass:"text-center"},[e._v("\n                No matches found\n              ")])]},proxy:!0},{key:"empty",fn:function(){return[o("div",{staticClass:"text-center"},[e._v("\n                No entries found\n              ")])]},proxy:!0}],null,!0)})],1)],1),o("div",{staticClass:"align-right",attrs:{slot:"footer"},slot:"footer"},[o("b-row",[o("b-col",{attrs:{md:""}},[e._v("\n            Showing "+e._s(Math.min(1+e.perPage*(e.currentPage-1),e.totalRows))+" to "+e._s(Math.min(e.perPage*(e.currentPage-1)+e.perPage,e.totalRows))+" of "+e._s(e.totalRows)+" entries\n          ")]),o("b-col",{attrs:{md:""}},[o("b-pagination",{attrs:{"total-rows":e.totalRows,"per-page":e.perPage,align:"right"},on:{input:e.loadRoles},model:{value:e.currentPage,callback:function(t){e.currentPage=t},expression:"currentPage"}})],1)],1)],1)],2)],1)],1)},i=[],s=(o("ac6a"),o("456d"),o("20d6"),o("bd86")),r=(o("6611"),o("450d"),o("e772")),l=o.n(r),c=(o("7f7f"),o("1f1a"),o("4e4b")),d=o.n(c),u=o("3d20"),m=o.n(u),p=o("8fe0"),f={mixins:[p["a"]],components:(a={},Object(s["a"])(a,d.a.name,d.a),Object(s["a"])(a,l.a.name,l.a),a),data:function(){return{loading:!1,bInitialQuery:!0,tableColumns:[{key:"role_name",label:"Role",sortable:!0,tdClass:"valign"},{key:"user_count",label:"User Count",sortable:!0,tdClass:"valign"},{key:"rule_count",label:"Rule Count",sortable:!0,tdClass:"valign"}],tableData:[],totalRows:1,currentPage:1,perPage:25,filterText:"",filterEntity:"",entities:[],sortBy:this.isAdmin()?"company_name":"role_name",sortDir:"asc",companies:[],showNewRoleModal:!1,model:{role_name:"",company_id:""},multipleCompanies:!1,canAction:!1}},methods:{loadRoles:function(){var e=this;this.loading=!0,this.$axios.post("/api/roles",{cur_page:this.currentPage,per_page:this.perPage,initial:this.bInitialQuery,filter:this.filterText,entity:this.filterEntity,sort_by:this.sortBy,sort_dir:this.sortDir?"desc":"asc"}).then((function(t){e.loading=!1,e.bInitialQuery=!1,(e.isAdmin()||e.userLimits("View","Roles","C").length>1)&&(e.multipleCompanies=!0,e.tableColumns.unshift({key:"company_name",label:"Entity",sortable:!0,tdClass:"valign"})),(e.isAdmin()||e.userCan("Edit","Roles")||e.userCan("Delete","Roles"))&&(e.canAction=!0,e.tableColumns.push({key:"actions",label:"Actions",thClass:"halign"})),e.tableData=t.data.rows,e.totalRows=t.data.total,e.entities=t.data.entities,0==e.totalRows&&(e.currentPage=1)}))},loadCompanies:function(){var e=this;this.loading=!0,this.$axios.post("/api/companies_list",{context:[{verb:"Add",module:"Roles"}]}).then((function(t){e.loading=!1,e.companies=t.data.companies}))},handleEdit:function(e,t){this.$router.push("/roles_manage/edit/"+t.id)},handleDelete:function(e,t){var o=this;m.a.fire({title:"Are you sure?",text:"Please confirm role removal",type:"warning",showCancelButton:!0,confirmButtonText:"Remove",buttonsStyling:!1,customClass:{cancelButton:"btn btn-outline-primary",confirmButton:"btn btn-primary"}}).then((function(e){e.value&&o.deleteRole(t)}))},deleteRole:function(e){var t=this;this.loading=!0,this.$axios.post("/api/role_destroy",{id:e.id}).then((function(o){if(t.loading=!1,"role_removed"==o.data.message){var a=t.tableData.findIndex((function(t){return t.id===e.id}));a>=0&&t.tableData.splice(a,1),t.$notify({title:"Success",message:"Role Removed",type:"success",verticalAlign:"top",horizontalAlign:"right"})}else"role_in_use"==o.data.message&&t.$notify({title:"Failure",message:"Cannot remove role: "+o.data.object_count+" "+o.data.object_type+" still using it.",type:"danger",verticalAlign:"top",horizontalAlign:"right"})}))},createRole:function(){var e=this;this.$refs.form.validate().then((function(t){t&&(e.loading=!0,e.$axios.post("/api/role_add",e.model).then((function(t){e.loading=!1,"role_added"==t.data.message&&(e.tableData.unshift(t.data.role),e.closeCreateRoleModal(),e.$notify({title:"Success",message:"New Role Created",type:"success",verticalAlign:"top",horizontalAlign:"right"}))})).catch((function(t){e.loading=!1,t.response.data.errors&&e.$refs.form.setErrors(t.response.data.errors)})))}))},showcreateRoleModal:function(){this.showNewRoleModal=!0},closeCreateRoleModal:function(){this.clearModel(),this.showNewRoleModal=!1},clearModel:function(){for(var e=Object.keys(this.model),t=0;t<e.length;t++)this.model[e[t]]=""},goToGroups:function(){this.$router.push({name:"groups_manage",params:{}})},sortingChanged:function(e){this.sortBy=e.sortBy,this.sortDir=e.sortDesc,this.loadRoles()}},watch:{filterText:function(e,t){this.currentPage=e!=t?1:this.currentPage}},mounted:function(){this.loadRoles(),this.loadCompanies()}},h=f,b=(o("077a"),o("2877")),g=Object(b["a"])(h,n,i,!1,null,null,null);t["default"]=g.exports},"8fe0":function(e,t,o){"use strict";o("a481");t["a"]={methods:{calcLastReadingColor:function(e){var t="black";return"undefined"!==typeof e.date_diff&&"N/A"!=e.date_diff&&(e.date_diff.h<3&&(t="green"),e.date_diff.h>=3&&e.date_diff.h<6&&(t="orange"),e.date_diff.h>=6&&(t="red"),(e.date_diff.d>0||e.date_diff.days>0)&&(t="red"),(e.date_diff.y>0||e.date_diff.m>0||e.date_diff.d>0||e.date_diff.days>0)&&(t="red")),t},isAdmin:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isAdmin(e)},isDistributor:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isDistributor(e)},isRestricted:function(){return this.$store.getters.isRestricted()},userCan:function(e,t){var o=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,a=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"";return this.$store.getters.userCan(e,t,o,a)},userLimits:function(e,t){var o=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return this.$store.getters.userLimits(e,t,o)},convertNodeTypeToGraphRouteName:function(e){return"Soil Moisture"==e?"soil_moisture_graph":"Nutrients"==e?"nutrients_graph":"Wells"==e?"well_controls_graph":"Water Meter"==e?"meters_graph":""},convertNodeTypeToSubsystem:function(e){return"Soil Moisture"==e?"Soil Moisture":"Nutrients"==e?"Nutrients":"Wells"==e?"Well Controls":"Water Meter"==e?"Meters":""},convertToInches:function(e){return e=e.replace("mm",""),e=parseInt(parseInt(e)/25)+'"',e},convertToIndex:function(e){return e=e.replace("mm",""),e=parseInt(parseInt(e)/100),e},truncateString:function(e){return e.length<=24?e:e.substring(0,21)+"..."}}}},d067:function(e,t,o){}}]);
//# sourceMappingURL=chunk-c0e90240.2092c9b0.js.map