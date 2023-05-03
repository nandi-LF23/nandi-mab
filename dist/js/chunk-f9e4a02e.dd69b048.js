(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-f9e4a02e"],{"5f9a":function(t,e,s){"use strict";s("877f")},6640:function(t,e){t.exports=function(t){var e={};function s(n){if(e[n])return e[n].exports;var i=e[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,s),i.l=!0,i.exports}return s.m=t,s.c=e,s.d=function(t,e,n){s.o(t,e)||Object.defineProperty(t,e,{configurable:!1,enumerable:!0,get:n})},s.n=function(t){var e=t&&t.__esModule?function(){return t["default"]}:function(){return t};return s.d(e,"a",e),e},s.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},s.p="/dist/",s(s.s=327)}({0:function(t,e){t.exports=function(t,e,s,n,i,r){var a,o=t=t||{},l=typeof t.default;"object"!==l&&"function"!==l||(a=t,o=t.default);var c,u="function"===typeof o?o.options:o;if(e&&(u.render=e.render,u.staticRenderFns=e.staticRenderFns,u._compiled=!0),s&&(u.functional=!0),i&&(u._scopeId=i),r?(c=function(t){t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,t||"undefined"===typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),n&&n.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(r)},u._ssrRegister=c):n&&(c=n),c){var d=u.functional,f=d?u.render:u.beforeCreate;d?(u._injectStyles=c,u.render=function(t,e){return c.call(e),f(t,e)}):u.beforeCreate=f?[].concat(f,c):[c]}return{esModule:a,exports:o,options:u}}},327:function(t,e,s){"use strict";e.__esModule=!0;var n=s(328),i=r(n);function r(t){return t&&t.__esModule?t:{default:t}}i.default.install=function(t){t.component(i.default.name,i.default)},e.default=i.default},328:function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=s(329),i=s.n(n),r=s(330),a=s(0),o=!1,l=null,c=null,u=null,d=a(i.a,r["a"],o,l,c,u);e["default"]=d.exports},329:function(t,e,s){"use strict";e.__esModule=!0,e.default={name:"ElStep",props:{title:String,icon:String,description:String,status:String},data:function(){return{index:-1,lineStyle:{},internalStatus:""}},beforeCreate:function(){this.$parent.steps.push(this)},beforeDestroy:function(){var t=this.$parent.steps,e=t.indexOf(this);e>=0&&t.splice(e,1)},computed:{currentStatus:function(){return this.status||this.internalStatus},prevStatus:function(){var t=this.$parent.steps[this.index-1];return t?t.currentStatus:"wait"},isCenter:function(){return this.$parent.alignCenter},isVertical:function(){return"vertical"===this.$parent.direction},isSimple:function(){return this.$parent.simple},isLast:function(){var t=this.$parent;return t.steps[t.steps.length-1]===this},stepsCount:function(){return this.$parent.steps.length},space:function(){var t=this.isSimple,e=this.$parent.space;return t?"":e},style:function(){var t={},e=this.$parent,s=e.steps.length,n="number"===typeof this.space?this.space+"px":this.space?this.space:100/(s-(this.isCenter?0:1))+"%";return t.flexBasis=n,this.isVertical||(this.isLast?t.maxWidth=100/this.stepsCount+"%":t.marginRight=-this.$parent.stepOffset+"px"),t}},methods:{updateStatus:function(t){var e=this.$parent.$children[this.index-1];t>this.index?this.internalStatus=this.$parent.finishStatus:t===this.index&&"error"!==this.prevStatus?this.internalStatus=this.$parent.processStatus:this.internalStatus="wait",e&&e.calcProgress(this.internalStatus)},calcProgress:function(t){var e=100,s={};s.transitionDelay=150*this.index+"ms",t===this.$parent.processStatus?(this.currentStatus,e=0):"wait"===t&&(e=0,s.transitionDelay=-150*this.index+"ms"),s.borderWidth=e?"1px":0,"vertical"===this.$parent.direction?s.height=e+"%":s.width=e+"%",this.lineStyle=s}},mounted:function(){var t=this,e=this.$watch("index",(function(s){t.$watch("$parent.active",t.updateStatus,{immediate:!0}),e()}))}}},330:function(t,e,s){"use strict";var n=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"el-step",class:[!t.isSimple&&"is-"+t.$parent.direction,t.isSimple&&"is-simple",t.isLast&&!t.space&&!t.isCenter&&"is-flex",t.isCenter&&!t.isVertical&&!t.isSimple&&"is-center"],style:t.style},[s("div",{staticClass:"el-step__head",class:"is-"+t.currentStatus},[s("div",{staticClass:"el-step__line",style:t.isLast?"":{marginRight:t.$parent.stepOffset+"px"}},[s("i",{staticClass:"el-step__line-inner",style:t.lineStyle})]),s("div",{staticClass:"el-step__icon",class:"is-"+(t.icon?"icon":"text")},["success"!==t.currentStatus&&"error"!==t.currentStatus?t._t("icon",[t.icon?s("i",{staticClass:"el-step__icon-inner",class:[t.icon]}):t._e(),t.icon||t.isSimple?t._e():s("div",{staticClass:"el-step__icon-inner"},[t._v(t._s(t.index+1))])]):s("i",{staticClass:"el-step__icon-inner is-status",class:["el-icon-"+("success"===t.currentStatus?"check":"close")]})],2)]),s("div",{staticClass:"el-step__main"},[s("div",{ref:"title",staticClass:"el-step__title",class:["is-"+t.currentStatus]},[t._t("title",[t._v(t._s(t.title))])],2),t.isSimple?s("div",{staticClass:"el-step__arrow"}):s("div",{staticClass:"el-step__description",class:["is-"+t.currentStatus]},[t._t("description",[t._v(t._s(t.description))])],2)])])},i=[],r={render:n,staticRenderFns:i};e["a"]=r}})},"877f":function(t,e,s){},"8fe0":function(t,e,s){"use strict";s("a481");e["a"]={methods:{calcLastReadingColor:function(t){var e="black";return"undefined"!==typeof t.date_diff&&"N/A"!=t.date_diff&&(t.date_diff.h<3&&(e="green"),t.date_diff.h>=3&&t.date_diff.h<6&&(e="orange"),t.date_diff.h>=6&&(e="red"),(t.date_diff.d>0||t.date_diff.days>0)&&(e="red"),(t.date_diff.y>0||t.date_diff.m>0||t.date_diff.d>0||t.date_diff.days>0)&&(e="red")),e},isAdmin:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isAdmin(t)},isDistributor:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";return this.$store.getters.isDistributor(t)},isRestricted:function(){return this.$store.getters.isRestricted()},userCan:function(t,e){var s=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,n=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"";return this.$store.getters.userCan(t,e,s,n)},userLimits:function(t,e){var s=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return this.$store.getters.userLimits(t,e,s)},convertNodeTypeToGraphRouteName:function(t){return"Soil Moisture"==t?"soil_moisture_graph":"Nutrients"==t?"nutrients_graph":"Wells"==t?"well_controls_graph":"Water Meter"==t?"meters_graph":""},convertNodeTypeToSubsystem:function(t){return"Soil Moisture"==t?"Soil Moisture":"Nutrients"==t?"Nutrients":"Wells"==t?"Well Controls":"Water Meter"==t?"Meters":""},convertToInches:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/25)+'"',t},convertToIndex:function(t){return t=t.replace("mm",""),t=parseInt(parseInt(t)/100),t},truncateString:function(t){return t.length<=24?t:t.substring(0,21)+"..."}}}},"95b0":function(t,e,s){t.exports=function(t){var e={};function s(n){if(e[n])return e[n].exports;var i=e[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,s),i.l=!0,i.exports}return s.m=t,s.c=e,s.d=function(t,e,n){s.o(t,e)||Object.defineProperty(t,e,{configurable:!1,enumerable:!0,get:n})},s.n=function(t){var e=t&&t.__esModule?function(){return t["default"]}:function(){return t};return s.d(e,"a",e),e},s.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},s.p="/dist/",s(s.s=323)}({0:function(t,e){t.exports=function(t,e,s,n,i,r){var a,o=t=t||{},l=typeof t.default;"object"!==l&&"function"!==l||(a=t,o=t.default);var c,u="function"===typeof o?o.options:o;if(e&&(u.render=e.render,u.staticRenderFns=e.staticRenderFns,u._compiled=!0),s&&(u.functional=!0),i&&(u._scopeId=i),r?(c=function(t){t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,t||"undefined"===typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),n&&n.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(r)},u._ssrRegister=c):n&&(c=n),c){var d=u.functional,f=d?u.render:u.beforeCreate;d?(u._injectStyles=c,u.render=function(t,e){return c.call(e),f(t,e)}):u.beforeCreate=f?[].concat(f,c):[c]}return{esModule:a,exports:o,options:u}}},323:function(t,e,s){"use strict";e.__esModule=!0;var n=s(324),i=r(n);function r(t){return t&&t.__esModule?t:{default:t}}i.default.install=function(t){t.component(i.default.name,i.default)},e.default=i.default},324:function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=s(325),i=s.n(n),r=s(326),a=s(0),o=!1,l=null,c=null,u=null,d=a(i.a,r["a"],o,l,c,u);e["default"]=d.exports},325:function(t,e,s){"use strict";e.__esModule=!0;var n=s(8),i=r(n);function r(t){return t&&t.__esModule?t:{default:t}}e.default={name:"ElSteps",mixins:[i.default],props:{space:[Number,String],active:Number,direction:{type:String,default:"horizontal"},alignCenter:Boolean,simple:Boolean,finishStatus:{type:String,default:"finish"},processStatus:{type:String,default:"process"}},data:function(){return{steps:[],stepOffset:0}},methods:{getMigratingConfig:function(){return{props:{center:"center is removed."}}}},watch:{active:function(t,e){this.$emit("change",t,e)},steps:function(t){t.forEach((function(t,e){t.index=e}))}}}},326:function(t,e,s){"use strict";var n=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"el-steps",class:[!t.simple&&"el-steps--"+t.direction,t.simple&&"el-steps--simple"]},[t._t("default")],2)},i=[],r={render:n,staticRenderFns:i};e["a"]=r},8:function(t,e){t.exports=s("2bb5")}})},"9c49":function(t,e,s){},c74d:function(t,e,s){"use strict";s.r(e);var n,i=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"content"},[s("base-header",{staticClass:"pb-6",attrs:{type:"activitylog"}},[s("b-row",{staticClass:"py-4",attrs:{"align-v":"center"}},[s("b-col",[s("h6",{staticClass:"h2 text-white d-inline-block mb-0 mr-5"},[t._v("Activity Logs")]),s("div",{directives:[{name:"show",rawName:"v-show",value:t.loading,expression:"loading"}],staticClass:"mab_spinner light right"})])],1)],1),s("b-container",{staticClass:"mt--6",attrs:{fluid:""}},[t.isAdmin()?s("card",{staticClass:"no-border-card",attrs:{"body-classes":"px-0 pb-1","footer-classes":"pb-2"}},[s("template",{slot:"header"},[t._v("\n        Log Entries\n      ")]),s("b-row",{staticClass:"nomargin"},[s("b-col",{attrs:{md:""}},[s("b-form-select",{on:{change:t.loadActivityLogs},model:{value:t.perPage,callback:function(e){t.perPage=e},expression:"perPage"}},[s("b-form-select-option",{attrs:{value:5}},[t._v("5")]),s("b-form-select-option",{attrs:{value:10}},[t._v("10")]),s("b-form-select-option",{attrs:{value:25,selected:""}},[t._v("25")]),s("b-form-select-option",{attrs:{value:50}},[t._v("50")])],1)],1),s("b-col",{attrs:{md:""}},[t.entities&&t.entities.length&&t.entities.length>1?s("el-select",{staticClass:"fullwidth",attrs:{clearable:"",placeholder:"Filter by Entity.."},on:{change:t.loadActivityLogs},model:{value:t.filterEntity,callback:function(e){t.filterEntity=e},expression:"filterEntity"}},t._l(t.entities,(function(t){return s("el-option",{key:t.id,attrs:{value:t.company_name,label:t.company_name}})})),1):t._e()],1),s("b-col",{attrs:{md:""}},[s("base-input",{attrs:{"prepend-icon":"fas fa-search"}},[s("b-input",{attrs:{debounce:1e3,placeholder:"Search..."},on:{update:t.loadActivityLogs},model:{value:t.filterText,callback:function(e){t.filterText=e},expression:"filterText"}})],1)],1)],1),s("b-row",{staticClass:"nomargin"},[s("b-col",{attrs:{md:""}},[s("b-table",{attrs:{striped:"",bordered:"",outlined:"",small:"",stacked:"md",responsive:"","show-empty":"","primary-key":"id","no-local-sorting":"",fields:t.tableColumns,items:t.tableData,busy:t.bInitialQuery},on:{"sort-changed":t.sortingChanged,"update:busy":function(e){t.bInitialQuery=e}},scopedSlots:t._u([{key:"cell()",fn:function(e){return[s("span",{staticClass:"tblcell"},[t._v(t._s(e.value))])]}},{key:"table-busy",fn:function(){return[s("div",{staticClass:"text-center"},[s("div",{staticClass:"mab_spinner"})])]},proxy:!0},{key:"emptyfiltered",fn:function(){return[s("div",{staticClass:"text-center"},[t._v("\n                No matches found\n              ")])]},proxy:!0},{key:"empty",fn:function(){return[s("div",{staticClass:"text-center"},[t._v("\n                No entries found\n              ")])]},proxy:!0}],null,!1,523115014)})],1)],1),s("div",{staticClass:"align-right",attrs:{slot:"footer"},slot:"footer"},[s("b-row",[s("b-col",{attrs:{md:""}},[t._v("\n            Showing "+t._s(Math.min(1+t.perPage*(t.currentPage-1),t.totalRows))+" to "+t._s(Math.min(t.perPage*(t.currentPage-1)+t.perPage,t.totalRows))+" of "+t._s(t.totalRows)+" entries\n          ")]),s("b-col",{attrs:{md:""}},[s("b-pagination",{attrs:{"total-rows":t.totalRows,"per-page":t.perPage,align:"right"},on:{input:t.loadActivityLogs},model:{value:t.currentPage,callback:function(e){t.currentPage=e},expression:"currentPage"}})],1)],1)],1)],2):t._e()],1)],1)},r=[],a=s("bd86"),o=(s("6611"),s("450d"),s("e772")),l=s.n(o),c=(s("1f1a"),s("4e4b")),u=s.n(c),d=(s("9c49"),s("6640")),f=s.n(d),p=(s("7f7f"),s("d2ac"),s("95b0")),h=s.n(p),_=s("8fe0"),v={mixins:[_["a"]],components:(n={},Object(a["a"])(n,h.a.name,h.a),Object(a["a"])(n,f.a.name,f.a),Object(a["a"])(n,u.a.name,u.a),Object(a["a"])(n,l.a.name,l.a),n),data:function(){return{loading:!1,bInitialQuery:!0,tableColumns:[{key:"company",label:"Entity",tdClass:"valign",sortable:!0},{key:"user",label:"User",tdClass:"valign",sortable:!0},{key:"subsystem",label:"Subsystem",tdClass:"valign",sortable:!0},{key:"operation",label:"Operation",tdClass:"valign",sortable:!0},{key:"details",label:"Details",tdClass:"valign",sortable:!0},{key:"occurred",label:"Occurred (UTC)",tdClass:"valign",sortable:!0}],tableData:[],entities:[],totalRows:1,currentPage:1,perPage:25,filterText:"",filterEntity:"",sortBy:"occurred",sortDir:"desc",refreshTimer:null}},methods:{loadActivityLogs:function(){var t=this;this.loading=!0,this.$axios.post("/api/activity_logs",{cur_page:this.currentPage,per_page:this.perPage,filter:this.filterText,entity:this.filterEntity,sort_by:this.sortBy,sort_dir:this.sortDir?"desc":"asc"}).then((function(e){t.loading=!1,t.tableData=e.data.rows,t.totalRows=e.data.total,t.entities=e.data.entities,0==t.totalRows&&(t.currentPage=1),t.bInitialQuery&&(t.bInitialQuery=!1,t.refreshTimer=setInterval((function(){t.loadActivityLogs()}),1e4))}))},sortingChanged:function(t){this.sortBy=t.sortBy,this.sortDir=t.sortDesc,this.loadActivityLogs()}},watch:{filterText:function(t,e){this.currentPage=t!=e?1:this.currentPage}},mounted:function(){this.loadActivityLogs()},beforeRouteLeave:function(t,e,s){this.refreshTimer&&clearInterval(this.refreshTimer),s()}},g=v,m=(s("5f9a"),s("2877")),b=Object(m["a"])(g,i,r,!1,null,null,null);e["default"]=b.exports},d2ac:function(t,e,s){}}]);
//# sourceMappingURL=chunk-f9e4a02e.dd69b048.js.map