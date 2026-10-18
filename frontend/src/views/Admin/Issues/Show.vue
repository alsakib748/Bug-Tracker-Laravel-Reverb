<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useIssueStore } from '@/stores/issues';
import { useAttachmentStore } from '@/stores/attachments';
import { useProjectStore } from '@/stores/projects';
import { useCommentStore } from '@/stores/comments';
import { useAuthStore } from '@/stores/auth';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Swal from 'sweetalert2';
import { storeToRefs } from 'pinia';

import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

// ... other imports
import CommentList from '@/components/comments/CommentList.vue';
import CommentForm from '@/components/comments/CommentForm.vue';

import AttachmentList from '@/components/attachments/AttachmentList.vue';
import AttachmentUpload from '@/components/attachments/AttachmentUpload.vue';

import IssueStatusSelect from '@/components/issues/IssueStatusSelect.vue';
import TransitionDialog from '@/components/issues/TransitionDialog.vue';
import IssueStatusTimeline from '@/components/issues/IssueStatusTimeline.vue';

import IssueLabels from '@/components/issues/IssueLabels.vue';
import LabelPicker from '@/components/issues/LabelPicker.vue';
import LabelManager from '@/components/issues/LabelManager.vue';
import WatchIssueButton from '@/components/issues/WatchIssueButton.vue';
import IssueWatchers from '@/components/issues/IssueWatchers.vue';
import IssueLinks from '@/components/issues/IssueLinks.vue';
import GitHubLinksPanel from '@/components/github/GitHubLinksPanel.vue';
import AiBugReportPanel from '@/components/ai/AiBugReportPanel.vue';

import { useIssueWorkflow } from '@/composables/useIssueWorkflow';
import { useIssueLabels } from '@/composables/useIssueLabels';
import { useIssueWatchers } from '@/composables/useIssueWatchers';
import { useIssueLinks } from '@/composables/useIssueLinks';
import { useGitHubLinks } from '@/composables/useGitHubLinks';

import echo from '@/services/echo';
import { applyBugReportSuggestions } from '@/services/aiBugReportApi';

const issue = ref(null);
const loading = ref(false);
const aiPanel = ref(null);

const route = useRoute();
const router = useRouter();

const issueStore = useIssueStore();
const projectStore = useProjectStore();
const commentStore = useCommentStore();
const authStore = useAuthStore();
const attachmentStore = useAttachmentStore();

const { attachments, loading: attachmentsLoading } = storeToRefs(attachmentStore);

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
} = useIssueWorkflow(route.params.id);

const showTransitionDialog = ref(false);
const selectedTransition = ref(null);

// Issue labels
const {
  issueLabels,
  projectLabels,
  loading: labelsLoading,
  saving: labelsSaving,
  loadIssueLabels,
  loadProjectLabels,
  updateLabels,
} = useIssueLabels(route.params.id);

const showLabelPicker = ref(false);
const showLabelManager = ref(false);

// Issue watchers
const {
  watchers,
  isWatching,
  loading: watchersLoading,
  toggling: watchersToggling,
  load: loadWatchers,
  toggle: toggleWatch,
} = useIssueWatchers(route.params.id);

// Issue links
const {
  links,
  loading: linksLoading,
  creating: linksCreating,
  error: linksError,
  load: loadLinks,
  create: createLink,
  remove: removeLink,
} = useIssueLinks(route.params.id);

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
} = useGitHubLinks(route.params.id);

const comments = ref([]);
const loadingComments = ref(false);
const editingComment = ref(null);
const showCommentForm = ref(false);

const actionLoading = ref(false);
const selectedAssignee = ref(null);
const projectMembers = ref([]);

const currentPageTitle = ref('Issue Details');

const canManageMembers = computed(() => {
  if (!issue.value) return false;
  return authStore.isAdmin || authStore.user?.id === issue.value.created_by;
});

const assistantContext = computed(() => {
  if (!issue.value) return {};
  return {
    title: issue.value.title,
    description: issue.value.description,
    type: issue.value.type,
    severity: issue.value.severity,
    priority: issue.value.priority,
  };
});

const onAssistantApply = async ({ analysisId, accepted, reviewed }) => {
  try {
    await applyBugReportSuggestions(analysisId, { accepted, ...reviewed });
    issue.value = await issueStore.fetchIssue(issue.value.id);
    await loadIssueLabels();
    aiPanel.value?.reset?.();
    Swal.fire('Applied', 'AI suggestions have been applied to the issue.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to apply the suggestions.', 'error');
  }
};

const openTransitionDialog = (toStatus) => {
  if (!toStatus) return;
  selectedTransition.value = toStatus;
  showTransitionDialog.value = true;
};

const confirmTransition = async ({ toStatus, reason }) => {
  try {
    const updated = await performTransition(toStatus, reason);
    issue.value = updated;
    showTransitionDialog.value = false;
    Swal.fire('Status updated', '', 'success');
  } catch (error) {
    // error message is exposed via workflowError and shown in the dialog
  }
};

// ---- Labels ----
const openLabelPicker = () => {
  showLabelPicker.value = true;
};

const openLabelManager = () => {
  showLabelManager.value = true;
};

const openLabelManagerFromPicker = () => {
  showLabelPicker.value = false;
  showLabelManager.value = true;
};

const saveLabels = async (labelIds) => {
  try {
    await updateLabels(labelIds);
    showLabelPicker.value = false;
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to update labels.', 'error');
  }
};

const removeLabel = async (label) => {
  try {
    const remaining = issueLabels.value
      .filter((item) => item.id !== label.id)
      .map((item) => item.id);
    await updateLabels(remaining);
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove label.', 'error');
  }
};

const onLabelsChanged = async () => {
  await loadProjectLabels(issue.value?.project?.id);
  await loadIssueLabels();
};

// ---- Watchers ----
const onToggleWatch = async () => {
  try {
    await toggleWatch();
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to update watch state.', 'error');
  }
};

// ---- Links ----
const onCreateLink = async (payload) => {
  try {
    await createLink(payload);
  } catch (error) {
    // error is exposed via linksError and shown in the IssueLinks component
  }
};

const onRemoveLink = async (link) => {
  try {
    await removeLink(link);
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove link.', 'error');
  }
};

// ---- GitHub links ----
const onGitHubLink = async (pullRequestId) => {
  try {
    await linkGitHubPr(pullRequestId);
  } catch (error) {
    // error is exposed via githubError and shown in the GitHubLinksPanel
  }
};

const onGitHubRemoveLink = async (link) => {
  try {
    await removeGitHubLink(link);
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to remove GitHub link.', 'error');
  }
};

const assignUser = async () => {
  if (!selectedAssignee.value) return;
  actionLoading.value = true;
  try {
    const updated = await issueStore.assignIssue(issue.value.id, selectedAssignee.value);
    issue.value = updated;
    selectedAssignee.value = null;
    await loadHistory();
    await loadAllowedTransitions();
    Swal.fire('Assigned', 'Issue assigned successfully.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to assign', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const reopen = async () => {
  actionLoading.value = true;
  try {
    const updated = await issueStore.reopenIssue(issue.value.id);
    issue.value = updated;
    await loadHistory();
    await loadAllowedTransitions();
    Swal.fire('Reopened', 'Issue has been reopened.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to reopen.', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const closeIssue = async () => {
  actionLoading.value = true;
  try {
    const updated = await issueStore.closeIssue(issue.value.id);
    issue.value = updated;
    await loadHistory();
    await loadAllowedTransitions();
    Swal.fire('Closed', 'Issue has been closed.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to close.', 'error');
  } finally {
    actionLoading.value = false;
  }
};

// comments
const loadComments = async () => {
  if (!issue.value) return;
  loadingComments.value = true;
  try {
    comments.value = await commentStore.fetchComments(issue.value.id);
  } catch (error) {
    console.error('Failed to load comments: ', error);
  } finally {
    loadingComments.value = false;
  }
}

const handleCommentSubmit = async (content) => {
  if (editingComment.value) {
    //update
    await commentStore.updateComment(editingComment.value.id, content);
    editingComment.value = null;
    // Re-fetch or update local - store already updates
  } else {
    // Create
    await commentStore.createComment(issue.value.id, content);
  }
  // Refresh list from store
  comments.value = commentStore.comments;
}

const startEdit = (comment) => {
  editingComment.value = comment;
};

const cancelEdit = () => {
  editingComment.value = null;
};

const deleteComment = async (commentId) => {
  await commentStore.deleteComment(commentId);
  comments.value = commentStore.comments;
}

// Load comments when issue loads

const loadAttachments = async () => {
  if (!issue.value) return;
  await attachmentStore.fetchAttachments(issue.value.id);
  // attachmentsLoading.value = true;
  // try {
  //   await attachmentStore.fetchAttachments(issue.value.id);
  //   attachments.value = attachmentStore.attachments;
  // } finally {
  //   attachmentsLoading.value = false;
  // }
};

const handleUploadAttachment = async (file) => {
  await attachmentStore.uploadAttachment(issue.value.id, file);
  // attachments.value = attachmentStore.attachments;
};

const handleDeleteAttachment = async (id) => {
  await attachmentStore.deleteAttachment(id);
  attachments.value = attachmentStore.attachments;
};


const loadIssue = async () => {
  loading.value = true;
  try {
    const data = await issueStore.fetchIssue(route.params.id);
    issue.value = data;

    // todo: Alternative Way.
    // if (issue.value) {
    //   commentStore.initializeListeners(issue.value.id);
    //   attachmentStore.initializeListeners(issue.value.id);
    // }

    if (data.project) {
      const members = await projectStore.fetchMembers(data.project.id);
      projectMembers.value = members;
    }
    await loadComments();
    await loadAttachments();
  } catch (error) {
    console.error('Error loading issue: ', error);
    router.push('/issues');
  } finally {
    loading.value = false;
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

      const exists = comments.value.some(
        item => item.id === comment.id
      )

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
      attachments.value = attachments.value.filter(
        item => item.id !== event.attachment_id
      )
    })
}

onMounted(async () => {

  await authStore.checkAuth()

  if (!authStore.authenticated) {
    return
  }

  const data = await issueStore.fetchIssue(route.params.id)
  issue.value = data

  // await loadIssue()

  if (!issue.value) {
    return
  }

  const issueId = issue.value.id

  // 4. Load project members
  if (issue.value.project?.id) {

    projectMembers.value =
      await projectStore.fetchMembers(
        issue.value.project.id
      )
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

});

onUnmounted(() => {

  if (!issue.value) {
    return
  }

  const issueId = issue.value.id

  echo.leave(`issue.${issueId}`)

  issueStore.stopListeningToIssue(issueId)

  commentStore.stopListeners?.(issueId)

  attachmentStore.stopListeners?.(issueId)
})


</script>

<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div v-if="issue" class="space-y-5 sm:space-y-6">
      <ComponentCard title="Issue Details">
        <div class="grid grid-cols-2 gap-4">
          <div><span class="font-medium">Project: </span> {{ issue.project?.name }}</div>
          <div><span class="font-medium">Type: </span> {{ issue.type_label }}</div>
          <div class="col-span-2"><span class="font-medium">Title: </span> {{ issue.title }}</div>
          <div class="col-span-2"><span class="font-medium">Description: </span> {{ issue.description || 'None' }}</div>
          <div><span class="font-medium">Priority: </span> {{ issue.priority_label }}</div>
          <div><span class="font-medium">Severity: </span> {{ issue.severity_label }}</div>
          <div><span class="font-medium">Status: </span> {{ issue.status_label }}</div>
          <div><span class="font-medium">Assignee: </span> {{ issue.assignee?.name || 'Unassigned' }}</div>
          <div><span class="font-medium">Reporter: </span> {{ issue.reporter?.name }}</div>
          <div><span class="font-medium">Due Date: </span> {{ issue.due_date ? new
            Date(issue.due_date).toLocaleDateString() : 'None' }}</div>
          <div><span class="font-medium">Estimated Hours: </span> {{ issue.estimated_hours || 'Not set' }}</div>
        </div>
        <hr class="my-4" />
        <h1 class="text-xl text-gray-700">Issue Assign To:</h1>

        <!-- Status Change (workflow) -->
        <IssueStatusSelect :currentStatus="issue.status" :allowedTransitions="allowedTransitions"
          :transitioning="transitioning" @transition="openTransitionDialog" />

        <div class="flex flex-wrap gap-2 mt-3">
          <!-- Assign -->
          <Select v-model="selectedAssignee" :options="projectMembers" optionLabel="name" optionValue="id"
            placeholder="Assign to" />
          <Button @click="assignUser" :loading="actionLoading" label="Assign"
            class="bg-green-600 text-white px-4 py-2 rounded" />

          <Button @click="reopen" v-if="issue.status === 'resolved'" label="Reopen"
            class="bg-yellow-600 text-white px-4 py-2 rounded" />
          <Button @click="closeIssue" v-if="issue.status === 'resolved'" label="Close"
            class="bg-gray-600 text-white px-4 py-2 rounded" />
        </div>


        <!-- Labels -->
        <div class="mt-4">
          <div class="flex flex-wrap items-center gap-3 mb-2">
            <h2 class="text-xl text-gray-700">Labels:</h2>
            <Button label="Edit" icon="pi pi-tag" class="p-button-text p-button-sm"
              @click="openLabelPicker" />
            <Button label="Manage" icon="pi pi-cog" class="p-button-text p-button-sm"
              @click="openLabelManager" />
          </div>
          <IssueLabels :labels="issueLabels" :loading="labelsLoading" removable @remove="removeLabel" />
        </div>

        <!-- AI Bug Report Assistant -->
        <div class="mt-6 rounded-lg border border-dashed border-indigo-300 p-4">
          <h2 class="mb-2 text-xl text-gray-700">AI Bug Report Assistant</h2>
          <AiBugReportPanel ref="aiPanel" :projectId="issue.project?.id" :issueId="issue.id"
            :context="assistantContext" :availableLabels="projectLabels" @apply="onAssistantApply" />
        </div>

        <!-- Watchers -->
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
          <IssueWatchers :watchers="watchers" :loading="watchersLoading" class="flex-1" />
          <WatchIssueButton :isWatching="isWatching" :toggling="watchersToggling" @toggle="onToggleWatch" />
        </div>

        <h1 class="text-xl text-gray-700">Issue Image/Screenshot:</h1>
        <AttachmentList :attachments="attachments" :loading="attachmentsLoading" @delete="handleDeleteAttachment" />
        <AttachmentUpload @upload="handleUploadAttachment" />

        <h1 class="text-xl text-gray-700">Comments:</h1>
        <CommentList :comments="comments" :loading="loadingComments" @edit="startEdit" @delete="deleteComment" />

        <CommentForm :editing="!!editingComment" :initialContent="editingComment ? editingComment.comment : ''"
          @submit="handleCommentSubmit" @cancelEdit="cancelEdit" />

        <!-- Linked issues -->
        <div class="mt-6">
          <IssueLinks :links="links" :loading="linksLoading" :creating="linksCreating" :error="linksError"
            @create="onCreateLink" @remove="onRemoveLink" />
        </div>

        <!-- GitHub development links -->
        <div class="mt-6 rounded-lg border border-gray-200 p-4">
          <GitHubLinksPanel :links="githubLinks" :candidates="githubCandidates" :loading="githubLoading"
            :linking="githubLinking" :error="githubError" @link="onGitHubLink" @remove="onGitHubRemoveLink" />
        </div>

        <!-- Status History -->
        <IssueStatusTimeline :history="statusHistory" :loading="workflowLoading" />

      </ComponentCard>
    </div>
    <div v-else>
      <div class="flex justify-center" style="margin-top: 60px;">
        <i class="pi pi-spin pi-spinner" style="font-size: 4rem"></i>
      </div>
    </div>

    <TransitionDialog v-model="showTransitionDialog" :fromStatus="issue?.status || ''" :toStatus="selectedTransition"
      :submitting="transitioning" :errorMessage="workflowError" @confirm="confirmTransition" />

    <LabelPicker v-model="showLabelPicker" :labels="issueLabels" :options="projectLabels" :loading="labelsLoading"
      :saving="labelsSaving" @save="saveLabels" @manage="openLabelManagerFromPicker" />

    <LabelManager v-model="showLabelManager" :projectId="issue?.project?.id" :labels="projectLabels"
      @changed="onLabelsChanged" />
  </AdminLayout>
</template>
