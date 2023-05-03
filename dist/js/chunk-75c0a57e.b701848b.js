(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-75c0a57e"],{"176a":function(t,e,n){"use strict";n.r(e);var a,s=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"content"},[n("base-header",{staticClass:"pb-6",attrs:{type:"wells"}},[n("b-row",{staticClass:"py-4",attrs:{"align-v":"center"}},[n("b-col",[n("h6",{staticClass:"h2 text-white d-inline-block mb-0 mr-5"},[t._v("Well Controls")]),n("div",{directives:[{name:"show",rawName:"v-show",value:t.loading,expression:"loading"}],staticClass:"mab_spinner light right"})])],1)],1),n("b-container",{staticClass:"mt--6",attrs:{fluid:""}},[n("card",{staticClass:"no-border-card",attrs:{"body-classes":"px-0 pb-1","footer-classes":"pb-2"}},[n("b-row",{staticClass:"nomargin"},[n("b-col",{attrs:{md:""}},[n("b-form-select",{on:{change:t.loadWells},model:{value:t.perPage,callback:function(e){t.perPage=e},expression:"perPage"}},[n("b-form-select-option",{attrs:{value:5}},[t._v("5")]),n("b-form-select-option",{attrs:{value:10}},[t._v("10")]),n("b-form-select-option",{attrs:{value:25,selected:""}},[t._v("25")]),n("b-form-select-option",{attrs:{value:50}},[t._v("50")])],1)],1),n("b-col",{attrs:{md:""}},[t.entities&&t.entities.length&&t.entities.length>1?n("el-select",{staticClass:"fullwidth",attrs:{clearable:"",filterable:"",placeholder:"Filter by Entity.."},on:{change:t.loadWells},model:{value:t.filterEntity,callback:function(e){t.filterEntity=e},expression:"filterEntity"}},t._l(t.entities,(function(t){return n("el-option",{key:t.id,attrs:{value:t.id,label:t.company_name}})})),1):t._e()],1),n("b-col",{attrs:{md:""}},[n("base-input",{attrs:{"prepend-icon":"fas fa-search"}},[n("b-input",{attrs:{debounce:1e3,placeholder:"Search..."},on:{update:t.loadWells},model:{value:t.filterText,callback:function(e){t.filterText=e},expression:"filterText"}})],1)],1)],1),n("b-row",{staticClass:"nomargin"},[n("b-col",{attrs:{md:""}},[n("b-table",{attrs:{striped:"",bordered:"",outlined:"",small:"",stacked:"lg",responsive:"","show-empty":"","primary-key":"node_address","no-local-sorting":"",fields:t.tableColumns,items:t.tableData,busy:t.bInitialQuery},on:{"sort-changed":t.sortingChanged,"update:busy":function(e){t.bInitialQuery=e}},scopedSlots:t._u([{key:"cell(field_name)",fn:function(e){return[n("strong",[t.userCan("Graph","Well Controls",e.item.id,"O")?n("router-link",{attrs:{to:{name:"well_controls_graph",params:{node_address:e.item.node_address}}}},[t._v("\n                  "+t._s(e.value)+"\n                ")]):[t._v("\n                  "+t._s(e.value)+"\n                ")]],2)]}},{key:"cell(date_time)",fn:function(e){return[n("div",{style:"color:"+t.calcLastReadingColor(e.item)},[t._v(t._s(e.value))])]}},{key:"cell()",fn:function(e){return[t._v("\n              "+t._s(e.value)+"\n            ")]}},t.canAction?{key:"cell(actions)",fn:function(e){return[n("div",{staticClass:"d-flex justify-content-center"},[n("b-button",{staticClass:"btn",attrs:{disabled:!t.userCan("Graph","Well Controls",e.item.id,"O")||t.loading,variant:"outline-primary",size:"sm",icon:""},on:{click:function(n){return t.handleGraph(e.index,e.item)}}},[t._v("\n                  Graph\n                  ")]),n("b-button",{staticClass:"btn",attrs:{disabled:!t.userCan("Edit","Well Controls",e.item.id,"O")||t.loading,variant:"outline-primary",size:"sm",icon:""},on:{click:function(n){return t.handleManage(e.index,e.item)}}},[t._v("\n                  Manage\n                  ")])],1)]}}:null,{key:"table-busy",fn:function(){return[n("div",{staticClass:"text-center"},[n("div",{staticClass:"mab_spinner"})])]},proxy:!0},{key:"emptyfiltered",fn:function(){return[n("div",{staticClass:"text-center"},[t._v("\n                No matches found\n              ")])]},proxy:!0},{key:"empty",fn:function(){return[n("div",{staticClass:"text-center"},[t._v("\n                No entries found\n              ")])]},proxy:!0}],null,!0)})],1)],1),n("div",{staticClass:"align-right",attrs:{slot:"footer"},slot:"footer"},[n("b-row",[n("b-col",{attrs:{md:""}},[t._v("\n            Showing "+t._s(Math.min(1+t.perPage*(t.currentPage-1),t.totalRows))+" to "+t._s(Math.min(t.perPage*(t.currentPage-1)+t.perPage,t.totalRows))+" of "+t._s(t.totalRows)+" entries\n          ")]),n("b-col",{attrs:{md:""}},[n("b-pagination",{attrs:{"total-rows":t.totalRows,"per-page":t.perPage,align:"right"},on:{input:t.loadWells},model:{value:t.currentPage,callback:function(e){t.currentPage=e},expression:"currentPage"}})],1)],1)],1)],1)],1)],1)},r=[],i=n("bd86"),l=(n("6611"),n("450d"),n("e772")),o=n.n(l),d=(n("7f7f"),n("1f1a"),n("4e4b")),c=n.n(d),u=n("8fe0"),f={mixins:[u["a"]],components:(a={},Object(i["a"])(a,c.a.name,c.a),Object(i["a"])(a,o.a.name,o.a),a),data:function(){return{loading:!1,tableLoading:!1,bInitialQuery:!0,filterable:["field_name","node_address","company_name"],tableColumns:[{key:"field_name",label:"Field Name",sortable:!0,tdClass:"valign"},{key:"node_address",label:"Node Address",sortable:!0,tdClass:"valign"},{key:"commissioning_date",label:"Installed",sortable:!0,tdClass:"valign"},{key:"date_time",label:"Last Update",sortable:!0,tdClass:"valign",sortByFormatted:!0,formatter:function(t,e,n){return"N/A"==t?null:t}},{key:"measurement_type",label:"Unit of Reading",sortable:!0,tdClass:"valign"}],tableData:[],totalRows:1,currentPage:1,perPage:25,filterText:"",filterEntity:null,entities:[],sortBy:"date_time",sortDir:"desc",canAction:!1}},methods:{loadWells:function(){var t=this;this.loading=!0,this.$axios.post("/api/WMTable",{cur_page:this.currentPage,per_page:this.perPage,initial:this.bInitialQuery,filter:this.filterText,entity:this.filterEntity,sort_by:this.sortBy,sort_dir:this.sortDir?"desc":"asc"}).then((function(e){t.loading=!1,t.bInitialQuery=!1,(t.isAdmin()||t.userLimits("View","Well Controls","C").length>1)&&t.tableColumns.unshift({key:"company_name",label:"Entity",sortable:!0,tdClass:"valign"}),(t.isAdmin()||t.userCan("Graph","Well Controls")||t.userCan("Edit","Well Controls"))&&(t.canAction=!0,t.tableColumns.push({key:"actions",label:"Actions",thClass:"halign"})),t.tableData=e.data.rows,t.totalRows=e.data.total,t.entities=e.data.entities,0==t.totalRows&&(t.currentPage=1)}))},handleGraph:function(t,e){this.$router.push({name:"well_controls_graph",params:{node_address:e.node_address}})},handleManage:function(t,e){this.$router.push({name:"well_controls_edit",params:{node_address:e.node_address}})},sortingChanged:function(t){this.sortBy=t.sortBy,this.sortDir=t.sortDesc,this.loadWells()}},watch:{filterText:function(t,e){this.currentPage=t!=e?1:this.currentPage}},mounted:function(){this.loadWells()}},b=f,m=(n("bd08"),n("2877")),p=Object(m["a"])(b,s,r,!1,null,null,null);e["default"]=p.exports},"8fe0":function(t,e,n){"use strict";n("a481");e["a"]={methods:{calcLastReadingColor:function(t){var e="black";return"undefined"!==typeof t.date_diff&&"N/A"!=t.date_diff&&(t.date_diff.h<3&&(e="green"),t.date_diff.h>=3&&t.date_diff.h<6&&(e="orange"),t.date_diff.h>=6&&(e="red"),(t.date_diff.d>0||t.date_diff.days>0)&&(e="red"),(t.date_diff.y>0||t.date_diff.m>0||t.date_diff.d>0||t.date_diff.days>0)&&(e="red")),e},isAdmin:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isAdmin(t)},isDistributor:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isDistributor(t)},isRestricted:function(){return this.$store.getters.isRestricted()},userCan:function(t,e){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,a=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"";return this.$store.getters.userCan(t,e,n,a)},userLimits:function(t,e){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return this.$store.getters.userLimits(t,e,n)},convertNodeTypeToGraphRouteName:function(t){return"Soil Moisture"==t?"soil_moisture_graph":"Nutrients"==t?"nutrients_graph":"Wells"==t?"well_controls_graph":"Water Meter"==t?"meters_graph":""},convertNodeTypeToSubsystem:function(t){return"Soil Moisture"==t?"Soil Moisture":"Nutrients"==t?"Nutrients":"Wells"==t?"Well Controls":"Water Meter"==t?"Meters":""},convertToInches:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/25)+'"',t},convertToIndex:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/100),t},truncateString:function(t){return t.length<=24?t:t.substring(0,21)+"..."}}}},bd08:function(t,e,n){"use strict";n("f58f")},f58f:function(t,e,n){}}]);
//# sourceMappingURL=chunk-75c0a57e.b701848b.js.map