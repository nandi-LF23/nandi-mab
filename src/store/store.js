import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'
import router from '@/routes/router'
import createPersistedState from "vuex-persistedstate";

Vue.use(Vuex);

const defaultState = function(){
    return {
        baseUrl: '',
        mapBoxAccessToken: 'pk.eyJ1IjoiZGF2ZWxmIiwiYSI6ImNsYXJ4Z3ludDF5MnIzc29id2VpaXMwbXUifQ.-y8z8jS9Tm6BJGoaULjwCg',
        mapBoxMapStyle: 'mapbox://styles/davelf/cl5grk342004114pvuo5mhar5',
        user_obj: null, // was ''
        mapSettingsCache: {
            visited: false,
            zoom: null,
            center: null,
            infoFilterType: null,
            nodeFilterType: null
        },
        logo: null,
        auth_notified: false
    };
};

const state = defaultState();

const getters = {
    getUOM: (state) => {
        // 1 = Celcius, 2 = Fahrenheit
        return state.user_obj && state.user_obj.unit_of_measure ? state.user_obj.unit_of_measure : 2;
    },
    getUOMEntity: (state) => {
        // Celcius Symbol = &#8451; Fahrenheit Symbol = &#8457;
        return state.user_obj && state.user_obj.unit_of_measure == 1 ? '&deg;C' : '&deg;F';
    },
    getMapState: (state) => {
        return state.mapSettingsCache;
    },
    getUserRole: (state) => {
        return state.user_obj.role;
    },
    getUser: (state) => {
        return state.user_obj;
    },
    getLogo: (state) => {
        return state.logo;
    },
    isAdmin: (state) => (email = '') => {
      if(!state.user_obj){ return false; }
      if(state.user_obj.is_admin){
        if(email == '') return true;
        return state.user_obj.email == email;
      } else return false;
    },
    isDistributor: (state) => (email = '') => {
        if(!state.user_obj){ return false; }
        if(state.user_obj.is_distributor){
          if(email == '') return true;
          return state.user_obj.email == email;
        } else return false;
    },
    isRestricted: (state) => () => {
        if(!state.user_obj){ return false; }
        if(state.user_obj.restricted_to){
            return true;
        }
        return false;
    },
    userCan: (state) => (permission, subsystem, object_id = null, object_type = '') => 
    {
        // console.log('userCan(' + permission + ', ' + subsystem + ', ' + object_id + ', ' + object_type + ')');
        // bail if not logged on
        if(!state.user_obj){ return false; }
        // always grant if admin
        if(state.user_obj.is_admin){ return true; }
        // grant Adding Entities if distributor
        if(state.user_obj.is_distributor && subsystem == 'Entities' && permission == 'Add'){ return true; }
        // route bypass
        if(subsystem === 'MAB'){ return true; } // ***

        let perms     = state.user_obj.perms;
        let company   = state.user_obj.company_id;
        let role      = state.user_obj.role_id;

        // tentatively deny
        let c_result    = false;
        let g_result    = false;
        let o_result    = false;
        let result      = false;

        // util
        let getNested = (obj, ...args) => {
            return args.reduce((obj, level) => obj && obj[level], obj)
        };

        // companies (list of company ids)
        let C = getNested(perms, company, role, subsystem, permission, 'C');
        if( typeof C !== 'undefined' && typeof C.length !== 'undefined' && C.length > 0){ /* tentatively grant */ c_result = result = true; }

        // groups (list of company ids)
        let G = getNested(perms, company, role, subsystem, permission, 'G');
        if( typeof G !== 'undefined' && typeof G.length !== 'undefined' && G.length > 0){ /* tentatively grant */ g_result = result = true; }

        // objects (list of object ids)
        let O = getNested(perms, company, role, subsystem, permission, 'O');
        if( typeof O !== 'undefined' && typeof O.length !== 'undefined' && O.length > 0){ /* tentatively grant */ o_result = result = true; }

        if ( object_id ){
            // c (company): Can we access these companies?
            if(c_result && object_type == 'C'){
                result = C.indexOf(object_id) !== -1; /* object_id should be a company_id */
            // g (group): Can we access these groups?
            } else if(g_result && object_type == 'G'){
                result = G.indexOf(object_id) !== -1; /* object_id should be a group_id */
            // o (object): Can we access this object?
            } else if(o_result && object_type == 'O'){
                result = O.indexOf(object_id) !== -1; // object_id is mixed
            // deny
            } else {
                result = false;
            }
        }

        // if no object_id specific query, then tentative grant becomes final grant

        return result;
    },

    userLimits: (state) => (permission, subsystem, object_type = '') => {
        // bail if not logged on
        if(!state.user_obj){ return false; }

        let perms     = state.user_obj.perms;
        let company   = state.user_obj.company_id;
        let role      = state.user_obj.role_id;
        let limits    = [];
        let C         = [];
        let G         = [];
        let O         = [];

        //console.log('userLimits perms:');
        //console.log(perms);

        // util
        let getNested = (obj, ...args) => {
            return args.reduce((obj, level) => obj && obj[level], obj)
        };

        // company limits
        if(object_type == 'C'){
            C = getNested(perms, company, role, subsystem, permission, 'C');
            if( typeof C !== 'undefined' && typeof C.length !== 'undefined' && C.length ){ limits = C; }
            //console.log("C Limits:");
            //console.log(C);

        // group limits
        } else if(object_type == 'G'){
            G = getNested(perms, company, role, subsystem, permission, 'G');
            if( typeof G !== 'undefined' && typeof G.length !== 'undefined' && G.length ){ limits = G; }
            //console.log("G Limits:");
            //console.log(G);

        // object limits
        } else if(object_type == 'O'){
            O = getNested(perms, company, role, subsystem, permission, 'O');
            if( typeof O !== 'undefined' && typeof O.length !== 'undefined' && O.length ){ limits = O; }
            //console.log("O Limits:");
            //console.log(O);
        }

        return limits;
    },

    getAuthNotified: (state) => {
        return state.auth_notified;
    }
}

const actions = {
    save_map_settings({ commit }, mapSettings ){
        return new Promise((resolve, reject) => {
            commit('MAP_SAVE_SETTINGS', mapSettings );
            resolve()
        })
    },

    update_uom({ commit }, uom){
        return new Promise((resolve, reject) => {
            commit('UPDATE_UOM', uom);
            resolve()
        })
    },

    update_user({ commit }, userObj ){
        return new Promise((resolve, reject) => {
            commit('UPDATE_USER', userObj );
            resolve()
        })
    },

    update_user_perms({ commit }, perms ){
        return new Promise((resolve, reject) => {
            commit('UPDATE_USER_PERMS', perms)
            resolve()
        })
    },

    do_login({ commit }, creds){
        return new Promise((resolve, reject) => {
            axios.post('api/login', creds)
            .then(resp => {
                if(resp.data.message == 'OK'){
                    commit('LOGGED_IN', resp.data );
                    resolve(resp);
                } else {
                    reject(err);
                }
            }).catch(err => reject(err));
        })
    },

    do_logout({ commit }){
        return new Promise((resolve, reject) => {
            axios.post('api/logout')
            .then(resp => {
                commit('LOGGED_OUT');
                resolve(resp);
                window.location.href = '/';
            }).catch(err => reject(err))
        })
    },

    clear_token({ commit }){
        return new Promise((resolve, reject) => {
            commit('CLEAR_TOKEN');
            resolve()
        })
    },
}

const mutations = {
    MAP_SAVE_SETTINGS(state, data){
        state.mapSettingsCache = data;
    },

    UPDATE_USER(state, data){
        state.user_obj = data;
    },

    UPDATE_UOM(state, uom){
        if(state.user_obj){
            state.user_obj.unit_of_measure = uom;
        }
    },

    UPDATE_USER_PERMS(state, data){
        if(state.user_obj){
            state.user_obj.perms = data;
        }
    },

    LOGGED_IN(state, data){
        Vue.prototype.$axios.defaults.headers['Authorization'] = 'Bearer ' + data.token;
        state.user_obj = data.user;
        state.user_obj.token = data.token;
        state.logo = data.logo ? data.logo : '/img/exe/logo_green.svg';
        // Override Redirect
        if(mabRedirect){
            data.redirect = mabRedirect;
            mabRedirect = null; // IF THIS CAUSES PROBLEMS, UNCOMMENT
        }
        if(!data.singlemode){
            router.replace('/' + data.redirect).catch((e) => { /* console.log(e);*/ });
        }
    },

    LOGGED_OUT(state){
        Vue.prototype.$axios.defaults.headers['Authorization'] = '';
        Object.assign(state, defaultState());
    },

    CLEAR_TOKEN(state){
        if(state.user_obj){
            state.user_obj.token = null;
            state.auth_notified = true;
        }
    }
}

const dataState = createPersistedState({
    rehydrated: (store) => {
        // Restore token if possible
        Vue.prototype.$axios = axios;
        if(store.getters.getUser && store.getters.getUser.token){
            Vue.prototype.$axios.defaults.headers['Authorization'] = 'Bearer ' + store.getters.getUser.token;
        }
    }
});

const store = new Vuex.Store({
    state,
    plugins: [ dataState ],
    getters,
    actions,
    mutations,
    strict: process.env.NODE_ENV !== 'production'
})

window.addEventListener('storage', event => {
    if (event.key === 'vuex') {
        if(localStorage.vuex){
            let s = JSON.parse(localStorage.vuex);
            if(!s || s.user_obj === null){
                router.replace('logout');
            }
        } else {
            router.replace('logout');
        }
    }
});

export default store;