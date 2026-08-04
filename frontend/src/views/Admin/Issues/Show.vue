<script setup>
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useIssueStore } from '@/stores/issues';
import { useProjectStore } from '@/stores/projects';
import { useCommentStore } from '@/stores/comments';
import { useAuthStore } from '@/stores/auth';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Swal from 'sweetalert2';

// ... other imports
import CommentList from '@/components/comments/CommentList.vue';
import CommentForm from '@/components/comments/CommentForm.vue';

const route = useRoute();
const router = useRouter();

const issueStore = useIssueStore();
const projectStore = useProjectStore();
const commentStore = useCommentStore();
const authStore = useAuthStore();

const comments = ref([]);
const loadingComments = ref(false);
const editingComment = ref(null);
const showCommentForm = ref(false);

const issue = ref(null);
const loading = ref(false);
const actionLoading = ref(false);
const selectedStatus = ref(null);
const selectedAssignee = ref(null);
const projectMembers = ref([]);

const currentPageTitle = ref('Issue Details');

const availableStatuses = computed(() => {
  if (!issue.value) return [];
  const transitions = {
    open: ['assigned'],
    assigned: ['in_progress'],
    in_progress: ['code_review'],
    code_review: ['testing'],
    testing: ['resolved', 'reopened'],
    resolved: ['closed', 'reopened'],
    reopened: ['in_progress'],
    closed: [],
  };

  const next = transitions[issue.value.status] || [];
  return next.map(s => ({
    label: s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '),
    value: s,
  }));

});


const changeStatus = async () => {
  if (!selectedStatus.value) return;
  actionLoading.value = true;
  try {
    const updated = await issueStore.changeStatus(issue.value.id, selectedStatus.value);
    issue.value = updated;
    selectedStatus.value = null;
    Swal.fire('Status update', '', 'success');
  } catch (error) {
    Swal.fire('error', error.response?.data?.message || 'Failed to change status', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const assignUser = async () => {
  if (!selectedAssignee.value) return;
  actionLoading.value = true;
  try {
    const updated = await issueStore.assignIssue(issue.value.id, selectedAssignee.value);
    issue.value = updated;
    selectedAssignee.value = null;
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
  loadComments.value = true;
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

const loadIssue = async () => {
  loading.value = true;
  try {
    const data = await issueStore.fetchIssue(route.params.id);
    issue.value = data;

    if (data.project) {
      const members = await projectStore.fetchMembers(data.project.id);
      projectMembers.value = members;
    }
    await loadComments();
  } catch (error) {
    console.error('Error loading issue: ', error);
    router.push('/issues');
  } finally {
    loading.value = false;
  }
}

onMounted(loadIssue);

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
        <div class="flex flex-wrap gap-2">
          <!-- Status Change -->
          <Select v-model="selectedStatus" :options="availableStatuses" optionLabel="label" optionValue="value"
            placeholder="Change status" />
          <Button @click="changeStatus" :loading="actionLoading" label="Update Status"
            class="bg-blue-600 text-white px-4 py-2 rounded" />

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

        <CommentList :comments="comments" :loading="loadingComments" @edit="startEdit" @delete="deleteComment" />

        <CommentForm :editing="!!editingComment" :initialContent="editingComment ? editingComment.comment : ''"
          @submit="handleCommentSubmit" @cancelEdit="cancelEdit" />

      </ComponentCard>
    </div>
  </AdminLayout>
</template>
