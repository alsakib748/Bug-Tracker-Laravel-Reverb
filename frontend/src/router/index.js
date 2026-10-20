import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import { ensurePrimeVue } from '@/plugins/primevue'

// First-paint routes stay eager: Login (landing), Forbidden (guard redirect
// target) and NotFound (catch-all). Every admin view is code-split so the
// entry chunk no longer carries the whole application.
import Login from '@/views/Auth/Signin.vue'
import NotFound from '@/views/Errors/FourZeroFour.vue'
import Forbidden from '@/views/Errors/Forbidden.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    alias: '/signin',
    component: Login,
    meta: { guest: true, primeVue: false },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Admin/Dashboard/DashboardPage.vue'),
    meta: { requiresAuth: true, permission: 'view dashboard', primeVue: false },
  },
  {
    path: '/users',
    name: 'UsersIndex',
    component: () => import('@/views/Admin/Users/Index.vue'),
    meta: { requiresAuth: true, permission: 'view users' },
  },
  {
    path: '/users/create',
    name: 'UsersCreate',
    component: () => import('@/views/Admin/Users/Create.vue'),
    meta: { requiresAuth: true, permission: 'create users' },
  },
  {
    path: '/users/:id/edit',
    name: 'UsersEdit',
    component: () => import('@/views/Admin/Users/Edit.vue'),
    meta: { requiresAuth: true, permission: 'update users' },
  },
  {
    path: '/roles',
    name: 'RolesIndex',
    component: () => import('@/views/Admin/Roles/Index.vue'),
    meta: { requiresAuth: true, permission: 'manage roles' },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/Admin/Profile.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/projects',
    name: 'ProjectsIndex',
    component: () => import('@/views/Admin/Projects/Index.vue'),
    meta: { requiresAuth: true, permission: 'view projects' },
  },
  {
    path: '/projects/create',
    name: 'ProjectsCreate',
    component: () => import('@/views/Admin/Projects/Create.vue'),
    meta: { requiresAuth: true, permission: 'create projects' },
  },
  {
    path: '/projects/:id/edit',
    name: 'ProjectsEdit',
    component: () => import('@/views/Admin/Projects/Edit.vue'),
    meta: { requiresAuth: true, permission: 'update projects' },
  },
  {
    path: '/projects/:id',
    name: 'ProjectsShow',
    component: () => import('@/views/Admin/Projects/Show.vue'),
    meta: { requiresAuth: true, permission: 'view projects' },
  },
  {
    path: '/issues',
    name: 'IssuesIndex',
    component: () => import('@/views/Admin/Issues/Index.vue'),
    meta: { requiresAuth: true, permission: 'view issues' },
  },
  {
    path: '/issues/create',
    name: 'IssuesCreate',
    component: () => import('@/views/Admin/Issues/Create.vue'),
    meta: { requiresAuth: true, permission: 'create issues' },
  },
  {
    path: '/issues/:id/edit',
    name: 'IssuesEdit',
    component: () => import('@/views/Admin/Issues/Edit.vue'),
    meta: { requiresAuth: true, permission: 'update issues' },
  },
  {
    path: '/issues/:id',
    name: 'IssuesShow',
    component: () => import('@/views/Admin/Issues/Show.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    name: 'NotificationsIndex',
    component: () => import('@/views/Admin/Notifications/Index.vue'),
    meta: { requiresAuth: true, permission: 'view notifications' },
  },
  {
    path: '/notifications/:id',
    name: 'NotificationsShow',
    component: () => import('@/views/Admin/Notifications/Show.vue'),
    meta: { requiresAuth: true, permission: 'view notifications' },
  },
  {
    path: '/activity-logs',
    name: 'ActivityLogsIndex',
    component: () => import('@/views/Admin/ActivityLogs/Index.vue'),
    meta: { requiresAuth: true, permission: 'view activity logs' },
  },
  {
    path: '/activity-logs/:id',
    name: 'ActivityLogsShow',
    component: () => import('@/views/Admin/ActivityLogs/Show.vue'),
    meta: { requiresAuth: true, permission: 'view activity logs' },
  },
  {
    path: '/board',
    name: 'BoardPage',
    component: () => import('@/views/Admin/Board/BoardPage.vue'),
    meta: { requiresAuth: true, permission: 'view board' },
  },
  {
    path: '/backlog',
    name: 'BacklogPage',
    component: () => import('@/views/Admin/Backlog/BacklogPage.vue'),
    meta: { requiresAuth: true, permission: 'view backlog' },
  },
  {
    path: '/automation',
    name: 'AutomationIndex',
    component: () => import('@/views/Admin/Automation/Index.vue'),
    meta: { requiresAuth: true, permission: 'view automation rules' },
  },
  {
    path: '/automation/create',
    name: 'AutomationCreate',
    component: () => import('@/views/Admin/Automation/Create.vue'),
    meta: { requiresAuth: true, permission: 'manage automation rules' },
  },
  {
    path: '/automation/:id/edit',
    name: 'AutomationEdit',
    component: () => import('@/views/Admin/Automation/Edit.vue'),
    meta: { requiresAuth: true, permission: 'manage automation rules' },
  },
  {
    path: '/automation/:id',
    name: 'AutomationShow',
    component: () => import('@/views/Admin/Automation/Show.vue'),
    meta: { requiresAuth: true, permission: 'view automation rules' },
  },
  {
    path: '/analytics',
    name: 'AnalyticsPage',
    component: () => import('@/views/Admin/Analytics/AnalyticsPage.vue'),
    meta: { requiresAuth: true, permission: 'view analytics' },
  },
  {
    path: '/github',
    name: 'GitHubIntegrationPage',
    component: () => import('@/views/Admin/GitHub/IntegrationPage.vue'),
    meta: { requiresAuth: true, permission: 'view github integration' },
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: Forbidden,
    meta: { primeVue: false },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: { primeVue: false },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Track the in-flight auth check so concurrent navigations share a single request
let authCheckPromise = null

const ensureAuthChecked = async (auth) => {
  if (auth.authenticated || auth.user !== null) return
  if (!authCheckPromise) {
    authCheckPromise = auth.checkAuth().finally(() => {
      authCheckPromise = null
    })
  }
  await authCheckPromise
}

router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore()

  await ensureAuthChecked(auth)

  if (to.meta.requiresAuth && !auth.authenticated) {
    return next({ name: 'Login' })
  }

  if (to.meta.guest && auth.authenticated) {
    return next({ name: 'Dashboard' })
  }

  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return next({ name: 'Forbidden' })
  }

  // Routes that render PrimeVue components load the plugin (and its theme) on
  // demand. Routes marked `primeVue: false` (shell, dashboard, error pages)
  // skip it entirely, keeping it off their critical path.
  if (to.meta.primeVue !== false) {
    await ensurePrimeVue()
  }

  next()
})

export default router
