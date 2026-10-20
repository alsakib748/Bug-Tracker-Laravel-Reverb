<template>
  <aside :class="[
    'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
    {
      'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
      'lg:w-[90px]': !isExpanded && !isHovered,
      'translate-x-0 w-[290px]': isMobileOpen,
      '-translate-x-full': !isMobileOpen,
      'lg:translate-x-0': true,
    },
  ]" @mouseenter="!isExpanded && (isHovered = true)" @mouseleave="isHovered = false">
    <div :class="['py-8 flex', !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-center']">
      <router-link to="/">
        <img v-if="isExpanded || isHovered || isMobileOpen" class="dark:hidden" src="/images/logo/logo.svg"
          alt="Traceflow" width="150" height="40" />
        <img v-if="isExpanded || isHovered || isMobileOpen" class="hidden dark:block" src="/images/logo/logo-dark.svg"
          alt="Traceflow" width="150" height="40" />
        <img v-if="!(isExpanded || isHovered || isMobileOpen)" class="dark:hidden" src="/images/logo/logo-icon.svg"
          alt="Traceflow" width="32" height="32" />
        <img v-if="!(isExpanded || isHovered || isMobileOpen)" class="hidden dark:block"
          src="/images/logo/logo-icon-dark.svg" alt="Traceflow" width="32" height="32" />
      </router-link>
    </div>
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2 :class="[
              'mb-4 text-xs uppercase flex leading-[20px] text-gray-500',
              !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
            ]">
              <template v-if="isExpanded || isHovered || isMobileOpen">
                {{ menuGroup.title }}
              </template>
              <HorizontalDots v-else />
            </h2>
            <ul class="flex flex-col gap-4">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button v-if="item.subItems" @click="toggleSubmenu(groupIndex, index)" :class="[
                  'menu-item group w-full',
                  {
                    'menu-item-active': isSubmenuOpen(groupIndex, index),
                    'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                  },
                  !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start',
                ]">
                  <span :class="[
                    isSubmenuOpen(groupIndex, index)
                      ? 'menu-item-icon-active'
                      : 'menu-item-icon-inactive',
                  ]">
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{
                    item.name
                  }}</span>
                  <ChevronDownIcon v-if="isExpanded || isHovered || isMobileOpen" :class="[
                    'ml-auto w-5 h-5 transition-transform duration-200',
                    {
                      'rotate-180 text-brand-500': isSubmenuOpen(groupIndex, index),
                    },
                  ]" />
                </button>
                <router-link v-else-if="item.path" :to="item.path" :class="[
                  'menu-item group',
                  {
                    'menu-item-active': isActive(item.path),
                    'menu-item-inactive': !isActive(item.path),
                  },
                ]">
                  <span :class="[
                    isActive(item.path) ? 'menu-item-icon-active' : 'menu-item-icon-inactive',
                  ]">
                    <component :is="item.icon" />
                  </span>
                  <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">{{
                    item.name
                  }}</span>
                  <span v-if="badgeFor(item) && (isExpanded || isHovered || isMobileOpen)"
                    class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-medium rounded-full bg-brand-500 text-white">
                    {{ badgeFor(item) }}
                  </span>
                </router-link>
                <transition @enter="startTransition" @after-enter="endTransition" @before-leave="startTransition"
                  @after-leave="endTransition">
                  <div v-show="isSubmenuOpen(groupIndex, index) && (isExpanded || isHovered || isMobileOpen)
                    ">
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <router-link :to="subItem.path" :class="[
                          'menu-dropdown-item',
                          {
                            'menu-dropdown-item-active': isActive(subItem.path),
                            'menu-dropdown-item-inactive': !isActive(subItem.path),
                          },
                        ]">
                          {{ subItem.name }}
                          <span class="flex items-center gap-1 ml-auto">
                            <span v-if="subItem.new" :class="[
                              'menu-dropdown-badge',
                              {
                                'menu-dropdown-badge-active': isActive(subItem.path),
                                'menu-dropdown-badge-inactive': !isActive(subItem.path),
                              },
                            ]">
                              new
                            </span>
                            <span v-if="subItem.pro" :class="[
                              'menu-dropdown-badge',
                              {
                                'menu-dropdown-badge-active': isActive(subItem.path),
                                'menu-dropdown-badge-inactive': !isActive(subItem.path),
                              },
                            ]">
                              pro
                            </span>
                          </span>
                        </router-link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <SidebarWidget v-if="isExpanded || isHovered || isMobileOpen" />
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import {
  LayoutDashboardIcon,
  UserGroupIcon,
  TaskIcon,
  TableIcon,
  ListIcon,
  BellIcon,
  ChatIcon,
  PieChartIcon,
  PlugInIcon,
  SettingsIcon,
  ChevronDownIcon,
  HorizontalDots,
  FolderIcon,
  BoxIcon,
  BoxCubeIcon
} from '../../icons'
import SidebarWidget from './SidebarWidget.vue'
import { useSidebar } from '@/composables/useSidebar'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const route = useRoute()
const auth = useAuthStore()
const notificationStore = useNotificationStore()

const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar()

const baseMenuGroups = [
  {
    title: 'Overview',
    items: [
      {
        icon: LayoutDashboardIcon,
        name: 'Dashboard',
        path: '/dashboard',
        permission: 'view dashboard',
      },
    ],
  },
  {
    title: 'Work Management',
    items: [
      {
        icon: UserGroupIcon,
        name: 'Projects',
        path: '/projects',
        permission: 'view projects',
      },
      {
        icon: TaskIcon,
        name: 'Issues',
        path: '/issues',
        permission: 'view issues',
      },
      {
        icon: TableIcon,
        name: 'Board',
        path: '/board',
        permission: 'view board',
      },
      {
        icon: ListIcon,
        name: 'Backlog & Sprints',
        path: '/backlog',
        permission: 'view backlog',
      },
    ],
  },
  {
    title: 'Collaboration',
    items: [
      {
        icon: BellIcon,
        name: 'Notifications',
        path: '/notifications',
        permission: 'view notifications',
        badge: 'unread',
      },
      {
        icon: ChatIcon,
        name: 'Activity Logs',
        path: '/activity-logs',
        permission: 'view activity logs',
      },
    ],
  },
  {
    title: 'Insights & Integrations',
    items: [
      {
        icon: PieChartIcon,
        name: 'Analytics',
        path: '/analytics',
        permission: 'view analytics',
      },
      {
        icon: BoxCubeIcon,
        name: 'GitHub',
        path: '/github',
        permission: 'view github integration',
      },
      {
        icon: PlugInIcon,
        name: 'Automation Rules',
        path: '/automation',
        permission: 'view automation rules',
      },
    ],
  },
  {
    title: 'Administration',
    items: [
      {
        icon: UserGroupIcon,
        name: 'Users',
        path: '/users',
        permission: 'view users',
      },
      {
        icon: SettingsIcon,
        name: 'Roles & Permissions',
        path: '/roles',
        permission: 'manage roles',
      }
    ],
  },
]

const canSeeItem = (item) => !item.permission || auth.hasPermission(item.permission)
const canSeeSubItem = (subItem) => !subItem.permission || auth.hasPermission(subItem.permission)

const menuGroups = computed(() =>
  baseMenuGroups
    .map((group) => ({
      ...group,
      items: group.items
        .filter(canSeeItem)
        .map((item) =>
          item.subItems ? { ...item, subItems: item.subItems.filter(canSeeSubItem) } : item,
        )
        .filter((item) => !item.subItems || item.subItems.length > 0),
    }))
    .filter((group) => group.items.length > 0),
)

const badgeFor = (item) => {
  if (item.badge === 'unread') {
    const count = notificationStore.unreadCount
    return count > 0 ? (count > 99 ? '99+' : String(count)) : null
  }
  return null
}

const isActive = (path) => route.path === path

const toggleSubmenu = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`
  openSubmenu.value = openSubmenu.value === key ? null : key
}

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.value.some((group) =>
    group.items.some(
      (item) => item.subItems && item.subItems.some((subItem) => isActive(subItem.path)),
    ),
  )
})

const isSubmenuOpen = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups.value[groupIndex].items[itemIndex].subItems?.some((subItem) =>
        isActive(subItem.path),
      ))
  )
}

const startTransition = (el) => {
  el.style.height = 'auto'
  const height = el.scrollHeight
  el.style.height = '0px'
  el.offsetHeight // force reflow
  el.style.height = height + 'px'
}

const endTransition = (el) => {
  el.style.height = ''
}
</script>
