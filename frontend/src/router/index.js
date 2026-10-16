import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

import Login from '@/views/Auth/Signin.vue'
import Signup from '@/views/Auth/Signup.vue'
import Dashboard from '@/views/Ecommerce.vue'
import NotFound from '@/views/Errors/FourZeroFour.vue'

// Import Users Pages
import UsersIndex from '@/views/Admin/Users/Index.vue'
import UsersCreate from '@/views/Admin/Users/Create.vue'
import UsersEdit from '@/views/Admin/Users/Edit.vue'
import Profile from '@/views/Admin/Profile.vue'

// Import Project Pages
import ProjectsIndex from '@/views/Admin/Projects/Index.vue'
import ProjectsCreate from '@/views/Admin/Projects/Create.vue'
import ProjectsEdit from '@/views/Admin/Projects/Edit.vue'
import ProjectsShow from '@/views/Admin/Projects/Show.vue'

// Import Issues Pages
import IssuesIndex from '@/views/Admin/Issues/Index.vue'
import IssuesCreate from '@/views/Admin/Issues/Create.vue'
import IssuesEdit from '@/views/Admin/Issues/Edit.vue'
import IssuesShow from '@/views/Admin/Issues/Show.vue'

// Import Notifications Pages
import NotificationsIndex from '@/views/Admin/Notifications/Index.vue'
import NotificationsShow from '@/views/Admin/Notifications/Show.vue'

// Import Activity Logs Pages
import ActivityLogsIndex from '@/views/Admin/ActivityLogs/Index.vue'

// Import Board & Backlog Pages
import BoardPage from '@/views/Admin/Board/BoardPage.vue'
import BacklogPage from '@/views/Admin/Backlog/BacklogPage.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    alias: '/signin',
    component: Login,
    meta: { guest: true },
  },
  {
    path: '/signup',
    name: 'Signup',
    component: Signup,
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'UsersIndex',
    component: UsersIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/users/create',
    name: 'UsersCreate',
    component: UsersCreate,
    meta: { requiresAuth: true },
  },
  {
    path: '/users/:id/edit',
    name: 'UsersEdit',
    component: UsersEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
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
    path: '/issues',
    name: 'IssuesIndex',
    component: IssuesIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/create',
    name: 'IssuesCreate',
    component: IssuesCreate,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/:id/edit',
    name: 'IssuesEdit',
    component: IssuesEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/:id',
    name: 'IssuesShow',
    component: IssuesShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    name: 'NotificationsIndex',
    component: NotificationsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications/:id',
    name: 'NotificationsShow',
    component: NotificationsShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/activity-logs',
    name: 'ActivityLogsIndex',
    component: ActivityLogsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/board',
    name: 'BoardPage',
    component: BoardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/backlog',
    name: 'BacklogPage',
    component: BacklogPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
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

  next()
})

export default router
