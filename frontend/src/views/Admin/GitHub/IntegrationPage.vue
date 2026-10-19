<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="'GitHub Integration'"
      description="Link GitHub repositories to a project and keep pull requests in sync with your issues."
    />

    <div class="space-y-4">
      <div class="rounded-card border border-line bg-surface p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <Select
            v-model="projectId"
            :options="projectOptions"
            optionLabel="name"
            optionValue="id"
            placeholder="Select project"
            class="w-64"
            @change="load"
          />
          <div v-if="canManage" class="flex items-center gap-2">
            <AppButton variant="outline" :loading="syncing" :onClick="sync">
              <i v-if="!syncing" class="pi pi-refresh" aria-hidden="true"></i>
              Sync repositories
            </AppButton>
            <AppButton :loading="connecting" :onClick="onConnectClick">
              <i v-if="!connecting" class="pi pi-github" aria-hidden="true"></i>
              Connect GitHub
            </AppButton>
          </div>
        </div>
      </div>

      <div v-if="installation" class="rounded-card border border-line bg-surface p-4 sm:p-6">
        <div class="flex items-center gap-2 text-sm text-ink dark:text-gray-300">
          <i class="pi pi-github text-lg"></i>
          <span class="font-medium">Connected as {{ installation.account_login }}</span>
          <AppBadge size="sm" :color="installation.status === 'active' ? 'success' : 'warning'">
            {{ installation.status }}
          </AppBadge>
        </div>
      </div>

      <ComponentCard title="Repositories">
        <AppSkeleton v-if="loading" :rows="6" />

        <ErrorState
          v-else-if="loadError"
          title="Couldn't load repositories"
          :description="loadError"
          @retry="load"
        />

        <div v-else-if="repositories.length" class="divide-y">
          <div
            v-for="repo in repositories"
            :key="repo.id"
            class="py-3 flex flex-wrap justify-between items-center gap-2"
          >
            <div class="min-w-0">
              <a
                :href="repo.html_url"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium text-ink hover:text-blue-600 hover:underline dark:text-gray-300"
              >
                {{ repo.full_name }}
              </a>
              <p class="text-xs text-ink-muted dark:text-gray-400">
                {{ repo.is_private ? 'Private' : 'Public' }}
                <span v-if="repo.default_branch"> · {{ repo.default_branch }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <AppBadge size="sm" :color="repo.linked ? 'success' : 'light'">
                {{ repo.linked ? 'Linked' : 'Not linked' }}
              </AppBadge>
              <template v-if="canManage">
                <AppButton
                  v-if="!repo.linked"
                  size="sm"
                  variant="ghost"
                  :onClick="() => linkRepo(repo.id)"
                >
                  <span class="text-blue-600 hover:underline">Link</span>
                </AppButton>
                <template v-else>
                  <AppButton
                    size="sm"
                    variant="ghost"
                    :loading="syncingRepoId === repo.id"
                    :onClick="() => syncRepo(repo)"
                  >
                    <i
                      v-if="syncingRepoId !== repo.id"
                      class="pi pi-refresh"
                      aria-hidden="true"
                    ></i>
                    Sync PRs
                  </AppButton>
                  <AppButton size="sm" variant="ghost" :onClick="() => unlinkRepo(repo.id)">
                    <span class="text-red-600 hover:underline">Unlink</span>
                  </AppButton>
                </template>
              </template>
            </div>
          </div>
        </div>

        <EmptyState
          v-else
          title="No repositories yet"
          description="No GitHub installation connected. Click Connect GitHub to install the app on your account or organization."
        />
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Select from 'primevue/select'
import Swal from 'sweetalert2'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import { useProjectStore } from '@/stores/projects'
import { usePermissions } from '@/composables/usePermissions'
import {
  fetchGitHubStatus,
  fetchGitHubRepositories,
  linkRepository,
  unlinkRepository,
  syncRepositoryPullRequests,
  connectGitHub,
  startGitHubConnect,
} from '@/services/githubApi'

const route = useRoute()
const projectStore = useProjectStore()
const { hasPermission } = usePermissions()
const canManage = computed(() => hasPermission('manage github integration'))

const projectId = ref(null)
const installation = ref(null)
const repositories = ref([])
const loading = ref(false)
const loadError = ref(null)
const syncing = ref(false)
const connecting = ref(false)
const syncingRepoId = ref(null)

const projectOptions = computed(() => projectStore.projects)

const load = async () => {
  if (!projectId.value) return
  loading.value = true
  loadError.value = null
  try {
    const [status, repos] = await Promise.all([
      fetchGitHubStatus(projectId.value),
      fetchGitHubRepositories(projectId.value),
    ])
    installation.value = status.data.installation
    repositories.value = repos.data.repositories
  } catch (e) {
    console.error('Failed to load GitHub integration:', e)
    loadError.value =
      e.response?.data?.message || 'Could not load GitHub integration. Please try again.'
  } finally {
    loading.value = false
  }
}

const sync = async () => {
  if (!installation.value) return
  syncing.value = true
  try {
    await connectGitHub(projectId.value, {
      installation_id: installation.value.github_installation_id,
    })
    await load()
    Swal.fire('Synced', 'Repositories refreshed from GitHub.', 'success')
  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to sync repositories.', 'error')
  } finally {
    syncing.value = false
  }
}

const onConnectClick = () => {
  if (!projectId.value) {
    Swal.fire('Select a project', 'Choose a project before connecting GitHub.', 'warning')
    return
  }
  startGitHubConnect()
}

const linkRepo = async (repoId) => {
  try {
    await linkRepository(projectId.value, { github_repository_id: repoId })
    await load()
    Swal.fire('Linked', 'Repository linked to project.', 'success')
  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to link repository.', 'error')
  }
}

const syncRepo = async (repo) => {
  syncingRepoId.value = repo.id
  try {
    await syncRepositoryPullRequests(projectId.value, repo.id)
    Swal.fire('Syncing', 'Pull requests are being fetched from GitHub.', 'success')
  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to start PR sync.', 'error')
  } finally {
    syncingRepoId.value = null
  }
}

const unlinkRepo = async (repoId) => {
  const result = await Swal.fire({
    title: 'Unlink repository?',
    text: 'Issues keep their existing GitHub links, but no new webhooks will match this project.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
  })
  if (!result.isConfirmed) return

  try {
    await unlinkRepository(projectId.value, repoId)
    await load()
  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to unlink repository.', 'error')
  }
}

onMounted(async () => {
  await projectStore.fetchAllProjects()
  if (projectOptions.value.length) {
    projectId.value = projectOptions.value[0].id
  }

  // GitHub App install redirect: /github?installation_id=123&setup_action=install
  const installationId = route.query.installation_id
  if (installationId && projectId.value) {
    connecting.value = true
    try {
      await connectGitHub(projectId.value, { installation_id: Number(installationId) })
      // Clean the query string so a refresh does not re-trigger connect.
      window.history.replaceState({}, '', window.location.pathname)
      Swal.fire('Connected', 'GitHub installation verified.', 'success')
    } catch (e) {
      Swal.fire(
        'Error',
        e.response?.data?.message || 'Failed to verify GitHub installation.',
        'error',
      )
    } finally {
      connecting.value = false
    }
  }

  await load()
})
</script>
