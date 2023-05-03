import store from '@/store/store'

const routes = [
    {
        path: '',
        component: () => import('@/views/Layout/AuthLayout'),
        children: [
          {
              path: '/',
              name: 'login',
              component: () => import('@/views/Pages/Login'),
              meta: { module: 'Login' }
          },
          {
              path: '/not-found',
              beforeEnter (to, from, next) {
                if (mabRedirect) { 
                    console.log('Clearing redirect');
                    mabRedirect = null; /* clear nonexisting url */ 
                }
                if (store.getters.getUser) {
                    next({ name: 'dashboard' })
                } else {
                    next({ name: 'login' })
                }
              }
          }
        ],
        meta: { module: 'MAB' }
    },
    {
      path: '',
      component: () => import('@/views/Layout/DashboardLayout'),
      children: [
        {
            path: '/map',
            name: 'map',
            component: () => import('@/views/Pages/Map'),
            meta: { module: 'Map' }
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('@/views/Pages/Dashboard'),
            meta: { module: 'Dashboard' }
        },
        {
            path: '/field_management',
            name: 'field_management',
            component: () => import('@/views/Pages/FieldManagement'),
            meta: { module: 'Field Management' }
        },
        {
            path: '/node_config',
            name: 'node_config',
            component: () => import('@/views/Pages/NodeConfigTable'),
            meta: { module: 'Node Config' }
        },
        {
            path: '/node_config/edit/:node_address',
            name: 'node_config_edit',
            component: () => import('@/views/Pages/NodeConfigForm'),
            meta: { module: 'Node Config' }
        },
        {
            path: '/soil_moisture',
            name: 'soil_moisture',
            component: () => import('@/views/Pages/SoilMoistureTable'),
            meta: { module: 'Soil Moisture' }
        },
        {
            path: '/soil_moisture/edit/:node_address',
            name: 'soil_moisture_edit',
            component: () => import('@/views/Pages/SoilMoistureForm'),
            meta: { module: 'Soil Moisture' }
        },
        {
            path: '/soil_moisture/graph/:node_address',
            name: 'soil_moisture_graph',
            component: () => import('@/views/Pages/SoilMoistureGraph'),
            meta: { module: 'Soil Moisture' }
        },
        {
            path: '/nutrients',
            name: 'nutrients',
            component: () => import('@/views/Pages/NutrientsTable'),
            meta: { module: 'Nutrients' }
        },
        {
            path: '/nutrients/edit/:node_address',
            name: 'nutrients_edit',
            component: () => import('@/views/Pages/NutrientsForm'),
            meta: { module: 'Nutrients' }
        },
        {
            path: '/nutrients/graph/:node_address',
            name: 'nutrients_graph',
            component: () => import('@/views/Pages/NutrientsGraph'),
            meta: { module: 'Nutrients' }
        },
        {
            path: '/cultivars/:field_id',
            name: 'cultivars',
            component: () => import('@/views/Pages/CultivarsForm'),
            meta: { module: 'Cultivars' }
        },
        {
            path: '/well_controls',
            name: 'well_controls',
            component: () => import('@/views/Pages/WellsTable'),
            meta: { module: 'Well Controls' }
        },
        {
            path: '/well_controls/edit/:node_address',
            name: 'well_controls_edit',
            component: () => import('@/views/Pages/WellsForm'),
            meta: { module: 'Well Controls' }
        },
        {
            path: '/well_controls/graph/:node_address',
            name: 'well_controls_graph',
            component: () => import('@/views/Pages/WellsGraph'),
            meta: { module: 'Well Controls' }
        },
        {
            path: '/meters',
            name: 'meters',
            component: () => import('@/views/Pages/MetersTable'),
            meta: { module: 'Meters' }
        },
        {
            path: '/meters/edit/:node_address',
            name: 'meters_edit',
            component: () => import('@/views/Pages/MetersForm'),
            meta: { module: 'Meters' }
        },
        {
            path: '/meters/graph/:node_address',
            name: 'meters_graph',
            component: () => import('@/views/Pages/MetersGraph'),
            meta: { module: 'Meters' }
        },
        {
            path: '/sensor_types',
            name: 'sensor_types',
            component: () => import('@/views/Pages/SensorTypeTable'),
            meta: { module: 'Sensor Types' }
        },
        {
            path: '/sensor_types/edit/:id',
            name: 'sensor_types_edit',
            component: () => import('@/views/Pages/SensorTypeForm'),
            meta: { module: 'Sensor Types' }
        },
        {
            path: '/entities_manage',
            name: 'entities_manage',
            component: () => import('@/views/Pages/CompanyManagementTable'),
            meta: { module: 'Entities' }
        },
        {
            path: '/entities_manage/edit/:id',
            name: 'entities_manage_edit',
            component: () => import('@/views/Pages/CompanyManagementForm'),
            meta: { module: 'Entities' }
        },
        {
            path: '/entities_report',
            name: 'entities_report',
            component: () => import('@/views/Pages/CompanyManagementReport'),
            meta: { module: 'Entities' }
        },
        {
            path: '/users_manage',
            name: 'users_manage',
            component: () => import('@/views/Pages/UserManagementTable'),
            meta: { module: 'Users' }
        },
        {
            path: '/users_manage/show/:email',
            name: 'users_manage_show',
            component: () => import('@/views/Pages/UserManagementShow'),
            meta: { module: 'Users' },
        },
        {
            path: '/users_manage/edit/:email',
            name: 'users_manage_edit',
            component: () => import('@/views/Pages/UserManagementForm'),
            meta: { module: 'Users' }
        },
        {
            path: '/roles_manage',
            name: 'roles_manage',
            component: () => import('@/views/Pages/UserRolesTable'),
            meta: { module: 'Roles' }
        },
        {
            path: '/roles_manage/edit/:id',
            name: 'roles_manage_edit',
            component: () => import('@/views/Pages/UserRolesForm'),
            meta: { module: 'Roles' }
        },
        {
            path: '/groups_manage',
            name: 'groups_manage',
            component: () => import('@/views/Pages/GroupManagementTable'),
            meta: { module: 'Groups' }
        },
        {
            path: '/connections',
            name: 'connections',
            component: () => import('@/views/Pages/ConnectionsTable'),
            meta: { module: 'Connections' }
        },
        {
            path: '/dataformats',
            name: 'dataformats',
            component: () => import('@/views/Pages/DataFormatsTable'),
            meta: { module: 'Data Formats' }
        },
        {
            path: '/activity_logs',
            name: 'activity_logs',
            component: () => import('@/views/Pages/ActivityLogsTable'),
            meta: { module: 'Groups' }
        },
        {
            path: '/logout',
            name: 'logout',
            component: () => { store.dispatch('do_logout', {}) },
            meta: { module: 'Logout' }
        },
        {
            path: '/integrate',
            name: 'integrate',
            redirect: to => {
                let user = store.getters.getUser
                console.log('integrate route');
                if(user){
                    return '/entities_manage/edit/' + user.company_id;
                } else {
                    mabRedirect = 'integrate';
                    return '/';
                }
            }
        }
      ],
      meta: { module: 'MAB' }
    },
    // catchall 404 Page
    {
        path: '*',
        redirect: '/not-found'
    }
];

export default routes;