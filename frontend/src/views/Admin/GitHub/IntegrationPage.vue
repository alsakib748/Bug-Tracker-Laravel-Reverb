<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="'GitHub Integration'" />

    <div class="space-y-4">
      <ComponentCard>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <Select v-model="projectId" :options="projectOptions" optionLabel="name" optionValue="id"
            placeholder="Select project" class="w-64" @change="load" />
          <div class="flex items-center gap-2">
            <Button label="Sync repositories" icon="pi pi-refresh" :loading="syncing" severity="secondary"
              outlined @click="sync" />
            <Button label="Connect GitHub" icon="pi pi-github" :loading="connecting" class="bg-gray-900 text-white"
              @click="onConnectClick" />
          </div>
        </div>
      </ComponentCard>

      <ComponentCard v-if="installation">
        <div class="flex items-center gap-2 text-sm text-gray-700">
          <i class="pi pi-github text-lg"></i>
          <span class="font-medium">Connected as {{ installation.account_login }}</span>
          <span class="px-2 py-0.5 rounded text-xs"
            :class="installation.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
            {{ installation.status }}
          </span>
        </div>
      </ComponentCard>

      <ComponentCard title="Repositories">
        <div v-if="loading" class="flex justify-center py-6">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>

        <div v-else-if="repositories.length" class="divide-y">
          <div v-for="repo in repositories" :key="repo.id" class="py-3 flex flex-wrap justify-between items-center gap-2">
            <div class="min-w-0">
              <a :href="repo.html_url" target="_blank" rel="noopener noreferrer"
                class="font-medium text-gray-800 hover:text-blue-600 hover:underline">
                {{ repo.full_name }}
              </a>
              <p class="text-xs text-gray-500">
                {{ repo.is_private ? 'Private' : 'Public' }}
                <span v-if="repo.default_branch"> · {{ repo.default_branch }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span :class="repo.linked ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                class="text-xs px-2 py-1 rounded">
                {{ repo.linked ? 'Linked' : 'Not linked' }}
              </span>
              <Button v-if="!repo.linked" label="Link" class="text-xs text-blue-600 hover:underline" text
                @click="linkRepo(repo.id)" />
              <template v-else>
                <Button label="Sync PRs" icon="pi pi-refresh" class="text-xs" text
                  :loading="syncingRepoId === repo.id" @click="syncRepo(repo)" />
                <Button label="Unlink" class="text-xs text-red-600 hover:underline" text
                  @click="unlinkRepo(repo.id)" />
              </template>
            </div>
          </div>
        </div>

        <div v-else class="text-center text-gray-500 py-6 text-sm">
          No GitHub installation connected. Click "Connect GitHub" to install the app on your account or organization.
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Swal from 'sweetalert2'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import { useProjectStore } from '@/stores/projects'
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

const projectId = ref(null)
const installation = ref(null)
const repositories = ref([])
const loading = ref(false)
const syncing = ref(false)
const connecting = ref(false)
const syncingRepoId = ref(null)

const projectOptions = computed(() => projectStore.projects)

const load = async () => {
  if (!projectId.value) return
  loading.value = true
  try {
    const [status, repos] = await Promise.all([
      fetchGitHubStatus(projectId.value),
      fetchGitHubRepositories(projectId.value),
    ])
    installation.value = status.data.installation
    repositories.value = repos.data.repositories
  } catch (e) {
    console.error('Failed to load GitHub integration:', e)
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
      Swal.fire('Error', e.response?.data?.message || 'Failed to verify GitHub installation.', 'error')
    } finally {
      connecting.value = false
    }
  }

  await load()
})
</script>
