<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useIssueStore } from '@/stores/issues'
import { useAttachmentStore } from '@/stores/attachments'
import { useProjectStore } from '@/stores/projects'
import { useCommentStore } from '@/stores/comments'
import { useAuthStore } from '@/stores/auth'
import Select from 'primevue/select'
import Swal from 'sweetalert2'
import { storeToRefs } from 'pinia'

import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'
import MarkdownContent from '@/components/common/MarkdownContent.vue'
import AppSkeleton from '@/components/common/AppSkeleton.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

import { extractErrorMessage } from '@/utils/errorHandler'
import {
  statusBadgeColor,
  priorityBadgeColor,
  severityBadgeColor,
  isOverdue,
  formatDate,
  formatDateTime,
} from '@/utils/issueMeta'

// ... other imports
import CommentList from '@/components/comments/CommentList.vue'
import CommentForm from '@/components/comments/CommentForm.vue'

import AttachmentList from '@/components/attachments/AttachmentList.vue'
import AttachmentUpload from '@/components/attachments/AttachmentUpload.vue'

import IssueStatusSelect from '@/components/issues/IssueStatusSelect.vue'
import TransitionDialog from '@/components/issues/TransitionDialog.vue'
import IssueStatusTimeline from '@/components/issues/IssueStatusTimeline.vue'

import IssueLabels from '@/components/issues/IssueLabels.vue'
import LabelPicker from '@/components/issues/LabelPicker.vue'
import LabelManager from '@/components/issues/LabelManager.vue'
import WatchIssueButton from '@/components/issues/WatchIssueButton.vue'
import IssueWatchers from '@/components/issues/IssueWatchers.vue'
import IssueLinks from '@/components/issues/IssueLinks.vue'
import GitHubLinksPanel from '@/components/github/GitHubLinksPanel.vue'
import AiBugReportPanel from '@/components/ai/AiBugReportPanel.vue'

import { usePermissions } from '@/composables/usePermissions'
import { useIssueWorkflow } from '@/composables/useIssueWorkflow'
import { useIssueLabels } from '@/composables/useIssueLabels'
import { useIssueWatchers } from '@/composables/useIssueWatchers'
import { useIssueLinks } from '@/composables/useIssueLinks'
import { useGitHubLinks } from '@/composables/useGitHubLinks'

import echo from '@/services/echo'
import { onRealtimeReconnect, debounce } from '@/services/realtime'
import { applyBugReportSuggestions } from '@/services/aiBugReportApi'

const issue = ref(null)
const loading = ref(false)
const loadError = ref(null)
const aiPanel = ref(null)

const route = useRoute()
const router = useRouter()

const issueStore = useIssueStore()
const projectStore = useProjectStore()
const commentStore = useCommentStore()
const authStore = useAuthStore()
const attachmentStore = useAttachmentStore()
const { hasPermission } = usePermissions()

const { attachments, loading: attachmentsLoading } = storeToRefs(attachmentStore)

// Issue workflow (status transitions + history)
const {
  history: statusHistory,
  allowedTransitions,
  loading: workflowLoading,
  transitioning,
  error: workflowError,
  loadHistory,
  loadAllowedTransitions,
  performTransition,
} = useIssueWorkflow(route.params.id)

const showTransitionDialog = ref(false)
const selectedTransition = ref(null)

// Issue labels
const {
  issueLabels,
  projectLabels,
  loading: labelsLoading,
  saving: labelsSaving,
  loadIssueLabels,
  loadProjectLabels,
  updateLabels,
} = useIssueLabels(route.params.id)

const showLabelPicker = ref(false)
const showLabelManager = ref(false)

// Issue watchers
const {
  watchers,
  isWatching,
  loading: watchersLoading,
  toggling: watchersToggling,
  load: loadWatchers,
  toggle: toggleWatch,
} = useIssueWatchers(route.params.id)

// Issue links
const {
  links,
  loading: linksLoading,
  creating: linksCreating,
  error: linksError,
  load: loadLinks,
  create: createLink,
  remove: removeLink,
} = useIssueLinks(route.params.id)

// GitHub development links (PRs / commits)
const {
  links: githubLinks,
  candidates: githubCandidates,
  loading: githubLoading,
  linking: githubLinking,
  error: githubError,
  load: loadGitHubLinks,
  loadCandidates: loadGitHubCandidates,
  link: linkGitHubPr,
  remove: removeGitHubLink,
} = useGitHubLinks(route.params.id)

const comments = ref([])
const loadingComments = ref(false)
const editingComment = ref(null)
const showCommentForm = ref(false)

const actionLoading = ref(false)
const selectedAssignee = ref(null)
const projectMembers = ref([])

const currentPageTitle = ref('Issue Details')

const issueOverdue = computed(() => isOverdue(issue.value))

const breadcrumbs = computed(() => {
  const crumbs = [{ label: 'Issues', to: '/issues' }]
  if (issue.value?.project) {
    crumbs.unshift({ label: issue.value.project.name, to: `/projects/${issue.value.project.id}` })
  }
  return crumbs
})

const breadcrumbDescription = computed(() => {
  if (!issue.value) return ''
  return [issue.value.type_label, issue.value.project?.name].filter(Boolean).join(' · ')
})

const goEdit = () => router.push(`/issues/${route.params.id}/edit`)

const canManageMembers = computed(() => {
  if (!issue.value) return false
  return authStore.isAdmin || authStore.user?.id === issue.value.created_by
})

const assistantContext = computed(() => {
  if (!issue.value) return {}
  return {
    title: issue.value.title,
    description: issue.value.description,
    type: issue.value.type,
    severity: issue.value.severity,
    priority: issue.value.priority,
  }
})

const onAssistantApply = async ({ analysisId, accepted, reviewed }) => {
  try {
    await applyBugReportSuggestions(analysisId, { accepted, ...reviewed })
    issue.value = await issueStore.fetchIssue(issue.value.id)
    await loadIssueLabels()
    aiPanel.value?.reset?.()
    Swal.fire('Applied', 'AI suggestions have been applied to the issue.', 'success')
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to apply the suggestions.', 'error')
  }
}

const openTransitionDialog = (toStatus) => {
  if (!toStatus) return
  selectedTransition.value = toStatus
  showTransitionDialog.value = true
}

const confirmTransition = async ({ toStatus, reason }) => {
  try {
    const updated = await performTransition(toStatus, reason)
    issue.value = updated
    showTransitionDialog.value = false
    Swal.fire('Status updated', '', 'success')
  } catch (error) {
    // error message is exposed via workflowError and shown in the dialog
  }
}

// ---- Labels ----
const openLabelPicker = () => {
  showLabelPicker.value = true
}

const openLabelManager = () => {
  showLabelManager.value = true
}

const openLabelManagerFromPicker = () => {
  showLabelPicker.value = false
  showLabelManager.value = true
}

const saveLabels = async (labelIds) => {
  try {
    await updateLabels(labelIds)
    showLabelPicker.value = false
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to update labels.', 'error')
  }
}

const removeLabel = async (label) => {
  try {
    const remaining = issueLabels.value
      .filter((item) => item.id !== label.id)
      .map((item) => item.id)
    await updateLabels(remaining)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove label.', 'error')
  }
}

const onLabelsChanged = async () => {
  await loadProjectLabels(issue.value?.project?.id)
  await loadIssueLabels()
}

// ---- Watchers ----
const onToggleWatch = async () => {
  try {
    await toggleWatch()
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to update watch state.', 'error')
  }
}

// ---- Links ----
const onCreateLink = async (payload) => {
  try {
    await createLink(payload)
  } catch (error) {
    // error is exposed via linksError and shown in the IssueLinks component
  }
}

const onRemoveLink = async (link) => {
  try {
    await removeLink(link)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove link.', 'error')
  }
}

// ---- GitHub links ----
const onGitHubLink = async (pullRequestId) => {
  try {
    await linkGitHubPr(pullRequestId)
  } catch (error) {
    // error is exposed via githubError and shown in the GitHubLinksPanel
  }
}

const onGitHubRemoveLink = async (link) => {
  try {
    await removeGitHubLink(link)
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove GitHub link.', 'error')
  }
}

const assignUser = async () => {
  if (!selectedAssignee.value) return
  actionLoading.value = true
  try {
    const updated = await issueStore.assignIssue(issue.value.id, selectedAssignee.value)
    issue.value = updated
    selectedAssignee.value = null
    await loadHistory()
    await loadAllowedTransitions()
    Swal.fire('Assigned', 'Issue assigned successfully.', 'success')
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to assign', 'error')
  } finally {
    actionLoading.value = false
  }
}

const reopen = async () => {
  actionLoading.value = true
  try {
    const updated = await issueStore.reopenIssue(issue.value.id)
    issue.value = updated
    await loadHistory()
    await loadAllowedTransitions()
    Swal.fire('Reopened', 'Issue has been reopened.', 'success')
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to reopen.', 'error')
  } finally {
    actionLoading.value = false
  }
}

const closeIssue = async () => {
  actionLoading.value = true
  try {
    const updated = await issueStore.closeIssue(issue.value.id)
    issue.value = updated
    await loadHistory()
    await loadAllowedTransitions()
    Swal.fire('Closed', 'Issue has been closed.', 'success')
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to close.', 'error')
  } finally {
    actionLoading.value = false
  }
}

// comments
const loadComments = async () => {
  if (!issue.value) return
  loadingComments.value = true
  try {
    comments.value = await commentStore.fetchComments(issue.value.id)
  } catch (error) {
    console.error('Failed to load comments: ', error)
  } finally {
    loadingComments.value = false
  }
}

const handleCommentSubmit = async (content) => {
  if (editingComment.value) {
    //update
    await commentStore.updateComment(editingComment.value.id, content)
    editingComment.value = null
    // Re-fetch or update local - store already updates
  } else {
    // Create
    await commentStore.createComment(issue.value.id, content)
  }
  // Refresh list from store
  comments.value = commentStore.comments
}

const startEdit = (comment) => {
  editingComment.value = comment
}

const cancelEdit = () => {
  editingComment.value = null
}

const deleteComment = async (commentId) => {
  await commentStore.deleteComment(commentId)
  comments.value = commentStore.comments
}

// Load comments when issue loads

const loadAttachments = async () => {
  if (!issue.value) return
  await attachmentStore.fetchAttachments(issue.value.id)
  // attachmentsLoading.value = true;
  // try {
  //   await attachmentStore.fetchAttachments(issue.value.id);
  //   attachments.value = attachmentStore.attachments;
  // } finally {
  //   attachmentsLoading.value = false;
  // }
}

const handleUploadAttachment = async (file) => {
  await attachmentStore.uploadAttachment(issue.value.id, file)
  // attachments.value = attachmentStore.attachments;
}

const handleDeleteAttachment = async (id) => {
  await attachmentStore.deleteAttachment(id)
  attachments.value = attachmentStore.attachments
}

const loadIssue = async () => {
  loading.value = true
  try {
    const data = await issueStore.fetchIssue(route.params.id)
    issue.value = data

    // todo: Alternative Way.
    // if (issue.value) {
    //   commentStore.initializeListeners(issue.value.id);
    //   attachmentStore.initializeListeners(issue.value.id);
    // }

    if (data.project) {
      const members = await projectStore.fetchMembers(data.project.id)
      projectMembers.value = members
    }
    await loadComments()
    await loadAttachments()
  } catch (error) {
    console.error('Error loading issue: ', error)
    router.push('/issues')
  } finally {
    loading.value = false
  }
}

const subscribeToIssueChannel = () => {
  if (!issue.value?.id) {
    console.warn('Cannot subscribe: issue ID missing')
    return
  }

  const issueId = issue.value.id

  echo
    .private(`issue.${issueId}`)

    // Issue status
    .listen('.issue.status.changed', (event) => {
      if (issue.value?.id === event.issue_id) {
        issue.value.status = event.new_status
        issue.value.status_label = event.status_label ?? issue.value.status_label
        loadHistory()
        loadAllowedTransitions()
      }
    })

    // Issue labels
    .listen('.issue.labels.updated', (event) => {
      if (issue.value?.id === event.issue_id) {
        loadIssueLabels()
      }
    })

    // Issue watchers
    .listen('.issue.watchers.updated', (event) => {
      if (issue.value?.id === event.issue_id) {
        loadWatchers()
      }
    })

    // Issue links
    .listen('.issue.link.created', (event) => {
      if (issue.value?.id === event.source_issue_id || issue.value?.id === event.target_issue_id) {
        loadLinks()
      }
    })

    .listen('.issue.link.deleted', (event) => {
      if (issue.value?.id === event.source_issue_id || issue.value?.id === event.target_issue_id) {
        loadLinks()
      }
    })

    // Comment
    .listen('.comment.created', (event) => {
      const comment = event.comment

      if (!comment) {
        return
      }

      const exists = comments.value.some((item) => item.id === comment.id)

      if (!exists) {
        comments.value.push(comment)
      }
    })

    // Attachment uploaded
    .listen('.attachment.uploaded', (event) => {
      if (event.attachment) {
        attachments.value.unshift(event.attachment)
      }
    })

    // Attachment deleted
    .listen('.attachment.deleted', (event) => {
      attachments.value = attachments.value.filter((item) => item.id !== event.attachment_id)
    })

    // Issue edited fields
    .listen('.issue.updated', (event) => {
      if (event.issue && issue.value?.id === event.issue.id) {
        issue.value = { ...issue.value, ...event.issue }
      }
    })

    // Issue assignment
    .listen('.issue.assigned', (event) => {
      if (issue.value?.id === event.issue_id) {
        issue.value.assigned_to = event.assigned_to
      }
    })

    // Comment edited
    .listen('.comment.updated', (event) => {
      const updated = event.comment
      if (!updated) return
      const index = comments.value.findIndex((c) => c.id === updated.id)
      if (index !== -1) comments.value.splice(index, 1, updated)
    })

    // Comment removed
    .listen('.comment.deleted', (event) => {
      comments.value = comments.value.filter((c) => c.id !== event.comment_id)
    })
}

// After a socket reconnect, refetch everything the detail view renders so no
// updates published while offline are missed.
const reloadFromRealtime = debounce(async () => {
  if (!issue.value?.id) return
  await loadComments()
  await loadAttachments()
  await loadHistory()
  await loadAllowedTransitions()
  await loadIssueLabels()
  await loadWatchers()
  await loadLinks()
}, 600)

let removeReconnectListener = null

const bootstrap = async () => {
  loading.value = true
  loadError.value = null

  try {
    await authStore.checkAuth()

    if (!authStore.authenticated) {
      return
    }

    const data = await issueStore.fetchIssue(route.params.id)
    issue.value = data

    if (!issue.value) {
      loadError.value = 'This issue could not be found. It may have been deleted.'
      return
    }

    // 4. Load project members
    if (issue.value.project?.id) {
      projectMembers.value = await projectStore.fetchMembers(issue.value.project.id)
    }

    // 5. Load comments
    await loadComments()

    // 6. Load attachments
    await loadAttachments()

    // 7. Load workflow data (status history + allowed transitions)
    await loadHistory()
    await loadAllowedTransitions()

    // 8. Load labels, watchers and links
    await loadIssueLabels()
    await loadProjectLabels(issue.value.project?.id)
    await loadWatchers()
    await loadLinks()
    await loadGitHubLinks()
    loadGitHubCandidates()

    if (issue.value?.id) {
      echo.leave(`issue.${issue.value.id}`)
    }

    subscribeToIssueChannel()

    removeReconnectListener = onRealtimeReconnect(reloadFromRealtime)
  } catch (error) {
    console.error('Error loading issue: ', error)
    loadError.value = extractErrorMessage(error) || 'Failed to load this issue.'
  } finally {
    loading.value = false
  }
}

const retryLoad = () => bootstrap()

onMounted(bootstrap)

onUnmounted(() => {
  if (removeReconnectListener) removeReconnectListener()

  if (!issue.value) {
    return
  }

  const issueId = issue.value.id

  echo.leave(`issue.${issueId}`)
})
</script>

<template>
  <AdminLayout>
    <PageBreadcrumb
      :pageTitle="issue ? `Issue #${issue.id}` : 'Issue Details'"
      :description="breadcrumbDescription"
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <AppButton
          v-if="issue && hasPermission('update issues')"
          size="sm"
          variant="outline"
          :onClick="goEdit"
        >
          <i class="pi pi-pen-to-square mr-1.5" aria-hidden="true"></i>Edit
        </AppButton>
      </template>
    </PageBreadcrumb>

    <AppSkeleton v-if="loading && !issue" :rows="8" class-name="max-w-4xl" />

    <ErrorState
      v-else-if="loadError && !issue"
      title="Couldn't load this issue"
      :description="loadError"
      @retry="retryLoad"
    />

    <div v-else-if="issue" class="grid gap-5 lg:grid-cols-3 sm:gap-6">
      <!-- Header -->
      <div class="lg:col-span-3">
        <div class="rounded-card border border-line bg-surface p-5 sm:p-6">
          <div class="flex flex-wrap items-center gap-2">
            <AppBadge size="sm" :color="statusBadgeColor(issue.status)">
              {{ issue.status_label }}
            </AppBadge>
            <AppBadge size="sm" :color="priorityBadgeColor(issue.priority)">
              {{ issue.priority_label }}
            </AppBadge>
            <AppBadge size="sm" variant="solid" color="light">
              {{ issue.type_label }}
            </AppBadge>
            <AppBadge
              v-if="issue.severity && issue.severity !== 'minor'"
              size="sm"
              :color="severityBadgeColor(issue.severity)"
            >
              {{ issue.severity_label }}
            </AppBadge>
            <AppBadge v-if="issueOverdue" size="sm" variant="solid" color="error">
              Overdue
            </AppBadge>
            <div class="ml-auto flex items-center gap-2">
              <WatchIssueButton
                :isWatching="isWatching"
                :toggling="watchersToggling"
                @toggle="onToggleWatch"
              />
            </div>
          </div>

          <h1 class="mt-3 text-2xl font-semibold text-ink dark:text-white/90">{{ issue.title }}</h1>

          <div
            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-muted dark:text-gray-400"
          >
            <router-link
              v-if="issue.project"
              :to="`/projects/${issue.project.id}`"
              class="font-medium text-brand-600 hover:underline dark:text-brand-400"
            >
              {{ issue.project.name }}
            </router-link>
            <span>
              Reported by
              <span class="font-medium text-gray-700 dark:text-gray-300">{{
                issue.reporter?.name || 'Unknown'
              }}</span>
            </span>
            <span>Created {{ formatDate(issue.created_at) }}</span>
            <span v-if="issue.due_date" :class="issueOverdue ? 'font-semibold text-error-600' : ''">
              Due {{ formatDate(issue.due_date) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Main column -->
      <div class="space-y-5 lg:col-span-2 sm:space-y-6">
        <ComponentCard title="Description" desc="What needs to be fixed or built">
          <MarkdownContent :source="issue.description" placeholder="No description provided." />
        </ComponentCard>

        <ComponentCard title="Development" desc="Linked pull requests and commits">
          <GitHubLinksPanel
            :links="githubLinks"
            :candidates="githubCandidates"
            :loading="githubLoading"
            :linking="githubLinking"
            :error="githubError"
            :canManage="hasPermission('manage github links')"
            @link="onGitHubLink"
            @remove="onGitHubRemoveLink"
          />
        </ComponentCard>

        <ComponentCard title="Attachments" desc="Screenshots, logs, and supporting files">
          <AttachmentList
            :attachments="attachments"
            :loading="attachmentsLoading"
            @delete="handleDeleteAttachment"
          />
          <AttachmentUpload
            v-if="hasPermission('upload attachments')"
            class="mt-4"
            @upload="handleUploadAttachment"
          />
        </ComponentCard>

        <ComponentCard title="Discussion" desc="Comments and conversation on this issue">
          <CommentList
            :comments="comments"
            :loading="loadingComments"
            @edit="startEdit"
            @delete="deleteComment"
          />
          <CommentForm
            v-if="hasPermission('create comments') || hasPermission('update comments')"
            class="mt-4"
            :editing="!!editingComment"
            :initialContent="editingComment ? editingComment.comment : ''"
            @submit="handleCommentSubmit"
            @cancelEdit="cancelEdit"
          />
        </ComponentCard>

        <ComponentCard title="Activity" desc="Status changes and other history">
          <IssueStatusTimeline :history="statusHistory" :loading="workflowLoading" />
        </ComponentCard>
      </div>

      <!-- Sidebar -->
      <div class="space-y-5 sm:space-y-6">
        <ComponentCard title="Details" desc="Status, assignment, and scheduling">
          <div class="space-y-4">
            <IssueStatusSelect
              v-if="hasPermission('change issue status')"
              :currentStatus="issue.status"
              :allowedTransitions="allowedTransitions"
              :transitioning="transitioning"
              @transition="openTransitionDialog"
            />

            <div v-if="hasPermission('assign issues')">
              <label
                for="issue-assignee"
                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
              >
                Assignee
              </label>
              <div class="flex gap-2">
                <Select
                  id="issue-assignee"
                  v-model="selectedAssignee"
                  :options="projectMembers"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Assign to"
                  class="w-full"
                />
                <AppButton
                  size="sm"
                  :loading="actionLoading"
                  :disabled="!selectedAssignee"
                  :onClick="assignUser"
                >
                  Assign
                </AppButton>
              </div>
            </div>

            <div v-if="issue.status === 'resolved'" class="flex flex-wrap gap-2">
              <AppButton
                v-if="hasPermission('reopen issues')"
                size="sm"
                variant="outline"
                :loading="actionLoading"
                :onClick="reopen"
              >
                Reopen
              </AppButton>
              <AppButton
                v-if="hasPermission('close issues')"
                size="sm"
                variant="danger"
                :loading="actionLoading"
                :onClick="closeIssue"
              >
                Close
              </AppButton>
            </div>

            <dl class="space-y-3 border-t border-line pt-4 text-sm">
              <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted dark:text-gray-400">Reporter</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                  {{ issue.reporter?.name || 'Unknown' }}
                </dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted dark:text-gray-400">Due date</dt>
                <dd
                  class="font-medium"
                  :class="issueOverdue ? 'text-error-600' : 'text-gray-800 dark:text-gray-200'"
                >
                  {{ issue.due_date ? formatDate(issue.due_date) : 'None' }}
                </dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted dark:text-gray-400">Estimated hours</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                  {{ issue.estimated_hours || 'Not set' }}
                </dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted dark:text-gray-400">Severity</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                  {{ issue.severity_label || '—' }}
                </dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-ink-muted dark:text-gray-400">Updated</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                  {{ formatDateTime(issue.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </ComponentCard>

        <ComponentCard title="Labels" desc="Categorize this issue">
          <div class="mb-3 flex items-center gap-2">
            <AppButton
              v-if="hasPermission('manage issue labels')"
              size="sm"
              variant="ghost"
              :onClick="openLabelPicker"
            >
              <i class="pi pi-tag mr-1" aria-hidden="true"></i>Edit
            </AppButton>
            <AppButton
              v-if="hasPermission('manage issue labels')"
              size="sm"
              variant="ghost"
              :onClick="openLabelManager"
            >
              <i class="pi pi-cog mr-1" aria-hidden="true"></i>Manage
            </AppButton>
          </div>
          <IssueLabels
            :labels="issueLabels"
            :loading="labelsLoading"
            :removable="hasPermission('manage issue labels')"
            @remove="removeLabel"
          />
        </ComponentCard>

        <ComponentCard title="Watchers" desc="People following this issue">
          <IssueWatchers :watchers="watchers" :loading="watchersLoading" />
        </ComponentCard>

        <ComponentCard title="Related issues" desc="Linked issues and dependencies">
          <IssueLinks
            :links="links"
            :loading="linksLoading"
            :creating="linksCreating"
            :error="linksError"
            :canManage="hasPermission('manage issue links')"
            @create="onCreateLink"
            @remove="onRemoveLink"
          />
        </ComponentCard>

        <ComponentCard
          v-if="hasPermission('use ai bug report')"
          title="AI Bug Report Assistant"
          desc="Suggested improvements for this report"
        >
          <AiBugReportPanel
            ref="aiPanel"
            :projectId="issue.project?.id"
            :issueId="issue.id"
            :context="assistantContext"
            :availableLabels="projectLabels"
            @apply="onAssistantApply"
          />
        </ComponentCard>
      </div>
    </div>

    <!-- Dialogs (always mounted) -->
    <TransitionDialog
      v-model="showTransitionDialog"
      :fromStatus="issue?.status || ''"
      :toStatus="selectedTransition"
      :submitting="transitioning"
      :errorMessage="workflowError"
      @confirm="confirmTransition"
    />

    <LabelPicker
      v-model="showLabelPicker"
      :labels="issueLabels"
      :options="projectLabels"
      :loading="labelsLoading"
      :saving="labelsSaving"
      @save="saveLabels"
      @manage="openLabelManagerFromPicker"
    />

    <LabelManager
      v-model="showLabelManager"
      :projectId="issue?.project?.id"
      :labels="projectLabels"
      @changed="onLabelsChanged"
    />
  </AdminLayout>
</template>
