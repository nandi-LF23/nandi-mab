(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-6448e0e7"],{"016f":function(e,t,a){},3557:function(e,t,a){"use strict";a("f7ef")},"40e8":function(e,t,a){"use strict";a.r(t);var s,o=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"content"},[a("base-header",{staticClass:"pb-6",attrs:{type:"groups"}},[a("b-row",{staticClass:"py-4",attrs:{"align-v":"center"}},[a("b-col",[a("h6",{staticClass:"h2 text-white d-inline-block mb-0 mr-5"},[e._v("Group Management")]),a("div",{directives:[{name:"show",rawName:"v-show",value:e.loading,expression:"loading"}],staticClass:"mab_spinner light right"})])],1)],1),a("b-container",{staticClass:"mt--6",attrs:{fluid:""}},[a("b-modal",{attrs:{centered:"","no-close-on-esc":"","no-close-on-backdrop":""},scopedSlots:e._u([{key:"modal-header",fn:function(t){t.close;return[a("h6",{staticClass:"modal-title",attrs:{slot:"header",id:"modal-title-default"},slot:"header"},[e._v("Create New Group")])]}},{key:"default",fn:function(t){t.hide;return[a("validation-observer",{ref:"addform",attrs:{slim:""}},[a("form",{attrs:{role:"form"},on:{submit:function(e){return e.preventDefault(),function(){return!1}.apply(null,arguments)}}},[a("b-row",[a("b-col",[a("base-input",{attrs:{vid:"group_name",rules:"required|max:50",name:"group name",placeholder:"Group Name"},model:{value:e.add_model.group_name,callback:function(t){e.$set(e.add_model,"group_name",t)},expression:"add_model.group_name"}})],1)],1),a("b-row",[a("b-col",[a("base-input",{attrs:{name:"company",rules:"required",vid:"company"}},[a("el-select",{attrs:{filterable:"",placeholder:"Entity",disabled:!e.isAdmin()&&e.companies.length<2,"value-key":"id"},model:{value:e.add_model.company,callback:function(t){e.$set(e.add_model,"company",t)},expression:"add_model.company"}},e._l(e.companies,(function(e){return a("el-option",{key:e.id,attrs:{label:e.label,value:e}})})),1)],1)],1),a("b-col",[a("base-input",{attrs:{name:"subsystem",rules:"required",vid:"subsystem"}},[a("el-select",{attrs:{filterable:"",placeholder:"Module","value-key":"id"},on:{"visible-change":function(t){return e.filterChanges("subsystem",e.add_model)}},model:{value:e.add_model.subsystem,callback:function(t){e.$set(e.add_model,"subsystem",t)},expression:"add_model.subsystem"}},e._l(e.subsystems,(function(e){return a("el-option",{key:e.id,attrs:{label:e.subsystem_name,value:e}})})),1)],1)],1)],1),a("b-row",[a("b-col",[a("base-input",{attrs:{vid:"group_members",name:"group members"}},[a("el-select",{attrs:{multiple:"",filterable:"",placeholder:"Group Members","value-key":"id"},model:{value:e.add_model.group_members,callback:function(t){e.$set(e.add_model,"group_members",t)},expression:"add_model.group_members"}},[e.add_model.subsystem&&["Map","Field Management","Dashboard","Node Config"].includes(e.add_model.subsystem.subsystem_name)?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Soil Moisture"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.add_model.company.id&&"Soil Moisture"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Nutrients"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.add_model.company.id&&"Nutrients"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Well Controls"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.add_model.company.id&&"Wells"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Meters"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.add_model.company.id&&"Water Meter"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Sensor Types"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Sensor Types'\">\n                        "),e._l(e.sensors,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Cultivars"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Cultivars'\">\n                        "),e._l(e.cultivars,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.add_model.subsystem&&"Cultivar Stages"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Cultivar Stages'\">\n                        "),e._l(e.ct_stages,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Cultivar Templates"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Cultivar Templates'\">\n                        "),e._l(e.ct_templates,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.add_model.subsystem&&"Nutrient Templates"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Nutrient Templates'\">\n                        "),e._l(e.nut_templates,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.add_model.subsystem&&"Users"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Users'\">\n                        "),e._l(e.users,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Roles"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Roles'\">\n                        "),e._l(e.roles,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.add_model.subsystem&&"Security Rules"==e.add_model.subsystem.subsystem_name?[e._v("'+add_model.company.id.toString()+'\"'].label + ' Security Rules'\">\n                        "),e._l(e.sec_rules,(function(t){return[t.company_id==e.add_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.add_model.subsystem&&"Companies"==e.add_model.subsystem.subsystem_name?[a("el-option-group",{attrs:{label:"Companies"}},[e._l(e.companies,(function(e){return[a("el-option",{key:e.id,attrs:{label:e.label,value:e}})]}))],2)]:e._e()],2)],1)],1)],1)],1)])]}},{key:"modal-footer",fn:function(t){t.ok,t.cancel,t.hide;return[a("b-button",{staticClass:"ml-auto",attrs:{variant:"outline-primary"},on:{click:e.closeAddGroupModal}},[e._v("Cancel")]),a("b-button",{attrs:{variant:"primary"},on:{click:e.addGroup}},[e._v("Create")])]}}]),model:{value:e.showNewGroupModal,callback:function(t){e.showNewGroupModal=t},expression:"showNewGroupModal"}}),a("b-modal",{attrs:{centered:"","no-close-on-esc":"","no-close-on-backdrop":""},scopedSlots:e._u([{key:"modal-header",fn:function(t){t.close;return[a("h6",{staticClass:"modal-title",attrs:{slot:"header",id:"modal-title-default"},slot:"header"},[e._v("Edit Group")])]}},{key:"default",fn:function(t){t.hide;return[a("validation-observer",{ref:"editform",attrs:{slim:""}},[a("form",{attrs:{role:"form"},on:{submit:function(e){return e.preventDefault(),function(){return!1}.apply(null,arguments)}}},[a("b-row",[a("b-col",[a("base-input",{attrs:{vid:"group_name",rules:"required|max:50",name:"group name",placeholder:"Group Name"},model:{value:e.edit_model.group_name,callback:function(t){e.$set(e.edit_model,"group_name",t)},expression:"edit_model.group_name"}})],1)],1),a("b-row",[a("b-col",[a("base-input",{attrs:{vid:"role",name:"company",rules:"required"}},[a("el-select",{attrs:{filterable:"",placeholder:"Entity",disabled:"","value-key":"id"},model:{value:e.edit_model.company,callback:function(t){e.$set(e.edit_model,"company",t)},expression:"edit_model.company"}},e._l(e.companies,(function(e){return a("el-option",{key:e.id,attrs:{label:e.label,value:e}})})),1)],1)],1),a("b-col",[a("base-input",{attrs:{name:"subsystem",rules:"required",vid:"subsystem"}},[a("el-select",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],attrs:{filterable:"",placeholder:"Module",disabled:"",title:"Specify the group's module","value-key":"id"},on:{"visible-change":function(t){return e.filterChanges("subsystem",e.edit_model)}},model:{value:e.edit_model.subsystem,callback:function(t){e.$set(e.edit_model,"subsystem",t)},expression:"edit_model.subsystem"}},e._l(e.subsystems,(function(e){return a("el-option",{key:e.id,attrs:{label:e.subsystem_name,value:e}})})),1)],1)],1)],1),a("b-row",[a("b-col",[a("base-input",{attrs:{vid:"group_members",name:"group members"}},[a("el-select",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],attrs:{multiple:"",filterable:"",title:"Specify the members of this group",placeholder:"Group Members","value-key":"id"},model:{value:e.edit_model.group_members,callback:function(t){e.$set(e.edit_model,"group_members",t)},expression:"edit_model.group_members"}},[e.edit_model.subsystem&&["Map","Field Management","Dashboard","Node Config"].includes(e.edit_model.subsystem.subsystem_name)?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Soil Moisture"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.edit_model.company.id&&"Soil Moisture"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Nutrients"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.edit_model.company.id&&"Nutrients"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Well Controls"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.edit_model.company.id&&"Wells"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Meters"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nodes'\">\n                        "),e._l(e.nodes,(function(t){return[t.company_id==e.edit_model.company.id&&"Water Meter"==t.meta?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Sensor Types"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Sensor Types'\">\n                        "),e._l(e.sensors,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Cultivars"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Cultivars'\">\n                        "),e._l(e.cultivars,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.edit_model.subsystem&&"Cultivar Stages"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Cultivar Stages'\">\n                        "),e._l(e.ct_stages,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Cultivar Templates"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Cultivar Templates'\">\n                        "),e._l(e.ct_templates,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.edit_model.subsystem&&"Nutrient Templates"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Nutrient Templates'\">\n                        "),e._l(e.nut_templates,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.edit_model.subsystem&&"Users"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Users'\">\n                        "),e._l(e.users,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Roles"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Roles'\">\n                        "),e._l(e.roles,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}},[a("span",{staticStyle:{float:"left"}},[e._v(e._s(t.label))]),a("span",{staticStyle:{float:"right",color:"#8492a6","font-size":"13px","margin-right":"1em"}},[e._v(e._s(t.meta))])]):e._e()]}))]:e.edit_model.subsystem&&"Security Rules"==e.edit_model.subsystem.subsystem_name?[e._v("'+edit_model.company.id.toString()+'\"'].label + ' Security Rules'\">\n                        "),e._l(e.sec_rules,(function(t){return[t.company_id==e.edit_model.company.id?a("el-option",{key:t.id,attrs:{label:t.label,value:t}}):e._e()]}))]:e.edit_model.subsystem&&"Companies"==e.edit_model.subsystem.subsystem_name?[a("el-option-group",{attrs:{label:"Companies"}},[e._l(e.companies,(function(e){return[a("el-option",{key:e.id,attrs:{label:e.label,value:e}})]}))],2)]:e._e()],2)],1)],1)],1)],1)])]}},{key:"modal-footer",fn:function(t){t.ok,t.cancel,t.hide;return[a("b-button",{staticClass:"ml-auto",attrs:{variant:"outline-primary"},on:{click:e.closeEditGroupModal}},[e._v("Cancel")]),a("b-button",{attrs:{variant:"primary"},on:{click:e.updateGroup}},[e._v("Save")])]}}]),model:{value:e.showChangeGroupModal,callback:function(t){e.showChangeGroupModal=t},expression:"showChangeGroupModal"}}),a("card",{staticClass:"no-border-card",attrs:{"body-classes":"px-0 pb-0","footer-classes":"pb-2"}},[a("template",{slot:"header"},[a("base-button",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],staticClass:"btn",attrs:{disabled:!e.userCan("Add","Groups")||e.loading,size:"sm",type:"primary",title:"Groups are used to provide fine-grained access control to resources. They are used with Security Rules.",icon:""},on:{click:e.showAddGroupModal}},[e._v("\n          Add Group\n        ")]),a("base-button",{directives:[{name:"b-tooltip",rawName:"v-b-tooltip.hover.top",modifiers:{hover:!0,top:!0}}],staticClass:"btn",attrs:{disabled:!e.userCan("View","Roles")||e.loading,type:"primary",size:"sm",title:"Navigate back to the roles module.",icon:""},nativeOn:{click:function(t){return e.goToRoles()}}},[e._v("\n          Manage Roles\n        ")])],1),a("b-row",{staticClass:"nomargin"},[a("b-col",{attrs:{md:""}},[a("b-form-select",{on:{change:e.loadGroups},model:{value:e.perPage,callback:function(t){e.perPage=t},expression:"perPage"}},[a("b-form-select-option",{attrs:{value:5}},[e._v("5")]),a("b-form-select-option",{attrs:{value:10}},[e._v("10")]),a("b-form-select-option",{attrs:{value:25,selected:""}},[e._v("25")]),a("b-form-select-option",{attrs:{value:50}},[e._v("50")])],1)],1),a("b-col",{attrs:{md:""}},[e.companies&&Object.keys(e.companies).length&&Object.keys(e.companies).length>1?a("el-select",{staticClass:"fullwidth",attrs:{clearable:"",filterable:"",placeholder:"Filter by Entity.."},on:{change:e.loadGroups},model:{value:e.filterEntity,callback:function(t){e.filterEntity=t},expression:"filterEntity"}},e._l(e.companies,(function(e){return a("el-option",{key:e.id,attrs:{value:e.id,label:e.label}})})),1):e._e()],1),a("b-col",{attrs:{md:""}},[a("base-input",{attrs:{"prepend-icon":"fas fa-search"}},[a("b-input",{attrs:{debounce:1e3,placeholder:"Search..."},on:{update:e.loadGroups},model:{value:e.filterText,callback:function(t){e.filterText=t},expression:"filterText"}})],1)],1)],1),a("b-row",{staticClass:"nomargin"},[a("b-col",{attrs:{md:""}},[a("b-table",{ref:"table",attrs:{striped:"",bordered:"",outlined:"",small:"",stacked:"lg",responsive:"","show-empty":"","primary-key":"id",fields:e.tableColumns,items:e.tableData,busy:e.bInitialQuery},on:{"sort-changed":e.sortingChanged,"update:busy":function(t){e.bInitialQuery=t}},scopedSlots:e._u([{key:"cell()",fn:function(t){return[e._v("\n              "+e._s(t.value)+"\n            ")]}},{key:"cell(group_members)",fn:function(t){return[a("div",{staticClass:"group_members_cell"},e._l(t.item.group_members,(function(t,s){return a("b-button",{key:s,attrs:{variant:"primary",size:"sm"}},[e._v(e._s(t.label))])})),1)]}},e.canAction?{key:"cell(actions)",fn:function(t){return[a("div",{staticClass:"d-flex justify-content-center"},[a("b-button",{staticClass:"btn",attrs:{disabled:!e.userCan("Edit","Groups",t.item.id,"O"),variant:"outline-primary",title:"Edit this group",size:"sm"},on:{click:function(a){return e.handleEdit(t.index,t.item)}}},[e._v("\n                Edit\n                ")]),a("b-button",{staticClass:"btn",attrs:{disabled:!e.userCan("Delete","Groups",t.item.id,"O"),title:"Delete this group",variant:"outline-primary",size:"sm"},on:{click:function(a){return e.handleDelete(t.index,t.item)}}},[e._v("\n                Remove\n                ")])],1)]}}:null,{key:"table-busy",fn:function(){return[a("div",{staticClass:"text-center"},[a("div",{staticClass:"mab_spinner"})])]},proxy:!0},{key:"emptyfiltered",fn:function(){return[a("div",{staticClass:"text-center"},[e._v("\n                No matches found\n              ")])]},proxy:!0},{key:"empty",fn:function(){return[a("div",{staticClass:"text-center"},[e._v("\n                No entries found\n              ")])]},proxy:!0}],null,!0)})],1)],1),a("div",{staticClass:"align-right",attrs:{slot:"footer"},slot:"footer"},[a("b-row",[a("b-col",{attrs:{md:""}},[e._v("\n            Showing "+e._s(Math.min(1+e.perPage*(e.currentPage-1),e.totalRows))+" to "+e._s(Math.min(e.perPage*(e.currentPage-1)+e.perPage,e.totalRows))+" of "+e._s(e.totalRows)+" entries\n          ")]),a("b-col",{attrs:{md:""}},[a("b-pagination",{attrs:{"total-rows":e.totalRows,"per-page":e.perPage,align:"right"},on:{input:e.loadGroups},model:{value:e.currentPage,callback:function(t){e.currentPage=t},expression:"currentPage"}})],1)],1)],1)],2)],1)],1)},l=[],n=a("bd86"),i=(a("016f"),a("450d"),a("486c")),r=a.n(i),d=(a("6611"),a("e772")),u=a.n(d),m=(a("7f7f"),a("1f1a"),a("4e4b")),c=a.n(m),p=a("3d20"),_=a.n(p),b=a("8fe0"),y={mixins:[b["a"]],components:(s={},Object(n["a"])(s,c.a.name,c.a),Object(n["a"])(s,u.a.name,u.a),Object(n["a"])(s,r.a.name,r.a),s),data:function(){return{loading:!1,tableLoading:!1,bInitialQuery:!0,tableColumns:[{key:"group_name",label:"Group Name",sortable:!0,tdClass:"valign"},{key:"subsystem.subsystem_name",label:"Module",sortable:!0,tdClass:"valign"},{key:"group_members",label:"Group Members",sortable:!0,thStyle:{width:"40% !important"},tdClass:"valign"}],nodes:[],sensors:[],cultivars:[],ct_stages:[],ct_templates:[],nut_templates:[],users:[],roles:[],groups:[],sec_rules:[],companies:[],subsystems:[],tableData:[],totalRows:1,currentPage:1,perPage:25,filterText:"",filterEntity:"",sortBy:this.isAdmin()||this.companies&&this.companies.length>1?"company_name":"group_name",sortDir:"asc",showNewGroupModal:!1,showChangeGroupModal:!1,add_model:{id:null,group_name:"",company:{id:null,label:""},subsystem:{id:null,subsystem_name:""},group_members:[]},edit_model:{id:null,group_name:"",company:{id:null,label:""},subsystem:{id:null,subsystem_name:""},group_members:[]},canAction:!1}},methods:{loadGroups:function(){var e=this;this.loading=!0,this.$axios.post(this.$store.state.baseUrl+"/api/groups",{cur_page:this.currentPage,per_page:this.perPage,initial:this.bInitialQuery,filter:this.filterText,entity:this.filterEntity,sort_by:this.sortBy,sort_dir:this.sortDir?"asc":"desc"}).then((function(t){e.loading=!1,e.bInitialQuery=!1,(e.isAdmin()||e.userLimits("View","Groups","C").length>1)&&e.tableColumns.unshift({key:"company.company_name",label:"Entity",sortable:!0,tdClass:"valign"}),(e.userCan("Edit","Groups")||e.userCan("Delete","Groups"))&&(e.canAction=!0,e.tableColumns.push({key:"actions",label:"Actions",thClass:"halign"})),e.tableData=t.data.groups_data,e.nodes=t.data.nodes,e.sensors=t.data.sensors,e.cultivars=t.data.cultivars,e.ct_stages=t.data.ct_stages,e.ct_templates=t.data.ct_templates,e.nut_templates=t.data.nut_templates,e.users=t.data.users,e.roles=t.data.roles,e.sec_rules=t.data.sec_rules,e.subsystems=t.data.subsystems,e.companies=t.data.companies,e.totalRows=t.data.total,0==e.totalRows&&(e.currentPage=1)}))},handleEdit:function(e,t){this.edit_model=JSON.parse(JSON.stringify(t)),this.showEditGroupModal()},handleDelete:function(e,t){var a=this;_.a.fire({title:"Group Deletion",text:"Please confirm group removal",type:"warning",showCancelButton:!0,confirmButtonText:"Remove",buttonsStyling:!1,customClass:{cancelButton:"btn btn-outline-primary",confirmButton:"btn btn-primary"}}).then((function(e){e.value&&a.deleteGroup(t)}))},addGroup:function(){var e=this;this.$refs.addform.validate().then((function(t){t&&(e.loading=!0,e.$axios.post(e.$store.state.baseUrl+"/api/group_add",e.add_model).then((function(t){e.loading=!1,"group_added"==t.data.message&&(e.closeAddGroupModal(),e.loadGroups(),e.$notify({title:"Success",message:"New group added",type:"success",verticalAlign:"top",horizontalAlign:"right"}))})).catch((function(t){e.loading=!1,t.response.data.errors&&e.$refs.addform.setErrors(t.response.data.errors)})))}))},updateGroup:function(){var e=this;this.$refs.editform.validate().then((function(t){t&&(e.loading=!0,e.$axios.post(e.$store.state.baseUrl+"/api/group_update",e.edit_model).then((function(t){e.loading=!1,"group_updated"==t.data.message&&(e.closeEditGroupModal(),e.loadGroups(),e.$notify({title:"Success",message:"Group updated",type:"success",verticalAlign:"top",horizontalAlign:"right"}))})).catch((function(t){e.loading=!1,t.response.data.errors&&e.$refs.editform.setErrors(t.response.data.errors)})))}))},deleteGroup:function(e){var t=this;this.loading=!0,this.$axios.post(this.$store.state.baseUrl+"/api/group_destroy",{id:e.id}).then((function(e){t.loading=!1,"group_removed"==e.data.message&&(t.loadGroups(),t.$notify({title:"Removed",message:"Group was removed",type:"success",verticalAlign:"top",horizontalAlign:"right"}))}))},showAddGroupModal:function(){this.showNewGroupModal=!0},closeAddGroupModal:function(){this.resetAddModel(),this.showNewGroupModal=!1},showEditGroupModal:function(){this.showChangeGroupModal=!0},closeEditGroupModal:function(){this.showChangeGroupModal=!1},resetAddModel:function(){this.add_model.group_id=null,this.add_model.group_name="",this.add_model.company={id:null,label:""},this.add_model.subsystem={id:null,label:""},this.add_model.group_members=[]},goToRoles:function(){this.$router.push({name:"roles_manage",params:{}})},filterChanges:function(e,t){"subsystem"==e&&(t.group_members.length=0)},sortingChanged:function(e){this.sortBy=e.sortBy,this.sortDir=e.sortDesc,this.loadGroups()}},watch:{filterText:function(e,t){this.currentPage=e!=t?1:this.currentPage}},mounted:function(){this.loadGroups()}},f=y,g=(a("3557"),a("2877")),v=Object(g["a"])(f,o,l,!1,null,null,null);t["default"]=v.exports},"486c":function(e,t,a){e.exports=function(e){var t={};function a(s){if(t[s])return t[s].exports;var o=t[s]={i:s,l:!1,exports:{}};return e[s].call(o.exports,o,o.exports,a),o.l=!0,o.exports}return a.m=e,a.c=t,a.d=function(e,t,s){a.o(e,t)||Object.defineProperty(e,t,{configurable:!1,enumerable:!0,get:s})},a.n=function(e){var t=e&&e.__esModule?function(){return e["default"]}:function(){return e};return a.d(t,"a",t),t},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},a.p="/dist/",a(a.s=147)}({0:function(e,t){e.exports=function(e,t,a,s,o,l){var n,i=e=e||{},r=typeof e.default;"object"!==r&&"function"!==r||(n=e,i=e.default);var d,u="function"===typeof i?i.options:i;if(t&&(u.render=t.render,u.staticRenderFns=t.staticRenderFns,u._compiled=!0),a&&(u.functional=!0),o&&(u._scopeId=o),l?(d=function(e){e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,e||"undefined"===typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),s&&s.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(l)},u._ssrRegister=d):s&&(d=s),d){var m=u.functional,c=m?u.render:u.beforeCreate;m?(u._injectStyles=d,u.render=function(e,t){return d.call(t),c(e,t)}):u.beforeCreate=c?[].concat(c,d):[d]}return{esModule:n,exports:i,options:u}}},1:function(e,t){e.exports=a("d010")},147:function(e,t,a){"use strict";t.__esModule=!0;var s=a(148),o=l(s);function l(e){return e&&e.__esModule?e:{default:e}}o.default.install=function(e){e.component(o.default.name,o.default)},t.default=o.default},148:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var s=a(149),o=a.n(s),l=a(150),n=a(0),i=!1,r=null,d=null,u=null,m=n(o.a,l["a"],i,r,d,u);t["default"]=m.exports},149:function(e,t,a){"use strict";t.__esModule=!0;var s=a(1),o=l(s);function l(e){return e&&e.__esModule?e:{default:e}}t.default={mixins:[o.default],name:"ElOptionGroup",componentName:"ElOptionGroup",props:{label:String,disabled:{type:Boolean,default:!1}},data:function(){return{visible:!0}},watch:{disabled:function(e){this.broadcast("ElOption","handleGroupDisabled",e)}},methods:{queryChange:function(){this.visible=this.$children&&Array.isArray(this.$children)&&this.$children.some((function(e){return!0===e.visible}))}},created:function(){this.$on("queryChange",this.queryChange)},mounted:function(){this.disabled&&this.broadcast("ElOption","handleGroupDisabled",this.disabled)}}},150:function(e,t,a){"use strict";var s=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("ul",{directives:[{name:"show",rawName:"v-show",value:e.visible,expression:"visible"}],staticClass:"el-select-group__wrap"},[a("li",{staticClass:"el-select-group__title"},[e._v(e._s(e.label))]),a("li",[a("ul",{staticClass:"el-select-group"},[e._t("default")],2)])])},o=[],l={render:s,staticRenderFns:o};t["a"]=l}})},"8fe0":function(e,t,a){"use strict";a("a481");t["a"]={methods:{calcLastReadingColor:function(e){var t="black";return"undefined"!==typeof e.date_diff&&"N/A"!=e.date_diff&&(e.date_diff.h<3&&(t="green"),e.date_diff.h>=3&&e.date_diff.h<6&&(t="orange"),e.date_diff.h>=6&&(t="red"),(e.date_diff.d>0||e.date_diff.days>0)&&(t="red"),(e.date_diff.y>0||e.date_diff.m>0||e.date_diff.d>0||e.date_diff.days>0)&&(t="red")),t},isAdmin:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isAdmin(e)},isDistributor:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isDistributor(e)},isRestricted:function(){return this.$store.getters.isRestricted()},userCan:function(e,t){var a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,s=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"";return this.$store.getters.userCan(e,t,a,s)},userLimits:function(e,t){var a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return this.$store.getters.userLimits(e,t,a)},convertNodeTypeToGraphRouteName:function(e){return"Soil Moisture"==e?"soil_moisture_graph":"Nutrients"==e?"nutrients_graph":"Wells"==e?"well_controls_graph":"Water Meter"==e?"meters_graph":""},convertNodeTypeToSubsystem:function(e){return"Soil Moisture"==e?"Soil Moisture":"Nutrients"==e?"Nutrients":"Wells"==e?"Well Controls":"Water Meter"==e?"Meters":""},convertToInches:function(e){return e=e.replace("mm",""),e=parseInt(parseInt(e)/25)+'"',e},convertToIndex:function(e){return e=e.replace("mm",""),e=parseInt(parseInt(e)/100),e},truncateString:function(e){return e.length<=24?e:e.substring(0,21)+"..."}}}},f7ef:function(e,t,a){}}]);
//# sourceMappingURL=chunk-6448e0e7.41803330.js.map