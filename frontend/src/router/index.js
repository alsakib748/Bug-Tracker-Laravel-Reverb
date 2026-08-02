import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// Import Project Pages

// Import Project Pages
import ProjectsIndex from '@/views/Admin/Projects/Index.vue'
import ProjectsCreate from '@/views/Admin/Projects/Create.vue'
import ProjectsEdit from '@/views/Admin/Projects/Edit.vue'
import ProjectsShow from '@/views/Admin/Projects/Show.vue'

// Import Project Members Page
// import ProjectMembers from '@/views/Admin/ProjectMembers/ProjectMembers.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    component: () => import('../views/Auth/Signin.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'Ecommerce',
    component: () => import('../views/Ecommerce.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/projects',
    name: 'ProjectsIndex',
    component: ProjectsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/create',
    name: 'ProjectsCreate',
    component: ProjectsCreate,
    meta: { requiresAuth: true }, // fixed
  },
  {
    path: '/projects/:id/edit',
    name: 'ProjectsEdit',
    component: ProjectsEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/:id',
    name: 'ProjectsShow',
    component: ProjectsShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/calendar',
    name: 'Calendar',
    component: () => import('../views/Others/Calendar.vue'),
    meta: {
      title: 'Calendar',
    },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('../views/Others/UserProfile.vue'),
    meta: {
      title: 'Profile',
    },
  },
  {
    path: '/form-elements',
    name: 'Form Elements',
    component: () => import('../views/Forms/FormElements.vue'),
    meta: {
      title: 'Form Elements',
    },
  },
  {
    path: '/basic-tables',
    name: 'Basic Tables',
    component: () => import('../views/Tables/BasicTables.vue'),
    meta: {
      title: 'Basic Tables',
    },
  },
  {
    path: '/line-chart',
    name: 'Line Chart',
    component: () => import('../views/Chart/LineChart/LineChart.vue'),
  },
  {
    path: '/bar-chart',
    name: 'Bar Chart',
    component: () => import('../views/Chart/BarChart/BarChart.vue'),
  },
  {
    path: '/alerts',
    name: 'Alerts',
    component: () => import('../views/UiElements/Alerts.vue'),
    meta: {
      title: 'Alerts',
    },
  },
  {
    path: '/avatars',
    name: 'Avatars',
    component: () => import('../views/UiElements/Avatars.vue'),
    meta: {
      title: 'Avatars',
    },
  },
  {
    path: '/badge',
    name: 'Badge',
    component: () => import('../views/UiElements/Badges.vue'),
    meta: {
      title: 'Badge',
    },
  },

  {
    path: '/buttons',
    name: 'Buttons',
    component: () => import('../views/UiElements/Buttons.vue'),
    meta: {
      title: 'Buttons',
    },
  },

  {
    path: '/images',
    name: 'Images',
    component: () => import('../views/UiElements/Images.vue'),
    meta: {
      title: 'Images',
    },
  },
  {
    path: '/videos',
    name: 'Videos',
    component: () => import('../views/UiElements/Videos.vue'),
    meta: {
      title: 'Videos',
    },
  },
  {
    path: '/blank',
    name: 'Blank',
    component: () => import('../views/Pages/BlankPage.vue'),
    meta: {
      title: 'Blank',
    },
  },

  {
    path: '/error-404',
    name: '404 Error',
    component: () => import('../views/Errors/FourZeroFour.vue'),
    meta: {
      title: '404 Error',
    },
  },

  {
    path: '/signin',
    name: 'Signin',
    component: () => import('../views/Auth/Signin.vue'),
    meta: {
      title: 'Signin',
    },
  },
  {
    path: '/signup',
    name: 'Signup',
    component: () => import('../views/Auth/Signup.vue'),
    meta: {
      title: 'Signup',
    },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, form, next) => {
  const auth = useAuthStore()

  // If we haven't checked auth yet, do it
  if (auth.user === null && !auth.authenticated) {
    await auth.checkAuth()
  }

  if (to.meta.requiresAuth && !auth.authenticated) {
    next({ name: 'Login' })
  } else if (to.meta.guest && auth.authenticated) {
    next({ name: 'Ecommerce' })
  } else {
    next()
  }
})

export default router
