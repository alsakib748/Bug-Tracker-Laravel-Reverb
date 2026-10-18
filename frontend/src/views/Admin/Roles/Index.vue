<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Roles & Permissions" />

    <div class="space-y-5 sm:space-y-6">
      <!-- Overview stats -->
      <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-900 dark:border-gray-800">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide text-gray-500 uppercase">Total Roles</span>
            <span class="flex items-center justify-center w-9 h-9 text-blue-600 rounded-xl bg-blue-50 dark:bg-blue-900/30 dark:text-blue-300">
              <i class="pi pi-users"></i>
            </span>
          </div>
          <p class="mt-3 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ roles.length }}</p>
        </div>
        <div class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-900 dark:border-gray-800">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide text-gray-500 uppercase">Permissions</span>
            <span class="flex items-center justify-center w-9 h-9 text-emerald-600 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-300">
              <i class="pi pi-key"></i>
            </span>
          </div>
          <p class="mt-3 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ totalPermissions }}</p>
        </div>
        <div class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-900 dark:border-gray-800">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide text-gray-500 uppercase">Assigned Members</span>
            <span class="flex items-center justify-center w-9 h-9 text-violet-600 rounded-xl bg-violet-50 dark:bg-violet-900/30 dark:text-violet-300">
              <i class="pi pi-user-plus"></i>
            </span>
          </div>
          <p class="mt-3 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ totalMembers }}</p>
        </div>
        <div class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-900 dark:border-gray-800">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide text-gray-500 uppercase">Unsaved Changes</span>
            <span class="flex items-center justify-center w-9 h-9 rounded-xl"
              :class="hasDirty ? 'text-amber-600 bg-amber-50 dark:bg-amber-900/30 dark:text-amber-300' : 'text-gray-400 bg-gray-100 dark:bg-gray-800'">
              <i class="pi pi-save"></i>
            </span>
          </div>
          <p class="mt-3 text-2xl font-semibold" :class="hasDirty ? 'text-amber-600' : 'text-gray-800 dark:text-white/90'">
            {{ dirtyRoles.length }}
          </p>
        </div>
      </div>

      <!-- Roles -->
      <ComponentCard title="Roles" desc="Each role bundles a set of permissions. Admin always has full access.">
        <template #header>
          <button
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
            @click="openCreate">
            <i class="pi pi-plus"></i>
            New Role
          </button>
        </template>

        <div v-if="loading" class="py-10 text-center">
          <i class="text-2xl text-blue-600 pi pi-spin pi-spinner"></i>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="(role, index) in roles" :key="role.id"
            class="relative p-4 transition bg-white border border-gray-200 rounded-xl group dark:bg-gray-900 dark:border-gray-800 hover:border-blue-400 dark:hover:border-blue-500">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center min-w-0 gap-2">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="accent(index).dot"></span>
                <h4 class="font-semibold text-gray-800 truncate dark:text-white/90">{{ role.name }}</h4>
                <span v-if="role.is_protected"
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                  <i class="text-[9px] pi pi-lock"></i> Protected
                </span>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <button class="flex items-center justify-center w-7 h-7 text-gray-500 rounded-md hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 disabled:opacity-40 disabled:cursor-not-allowed"
                  :disabled="role.is_protected" title="Rename role" @click="openRename(role)">
                  <i class="text-xs pi pi-pencil"></i>
                </button>
                <button class="flex items-center justify-center w-7 h-7 text-gray-500 rounded-md hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-40 disabled:cursor-not-allowed"
                  :disabled="role.is_protected" title="Delete role" @click="confirmDelete(role)">
                  <i class="text-xs pi pi-trash"></i>
                </button>
              </div>
            </div>

            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">
                <i class="pi pi-users"></i> {{ role.users_count }} members
              </span>
              <span class="inline-flex items-center gap-1">
                <i class="pi pi-key"></i> {{ grantedCount(role.id) }} permissions
              </span>
            </div>

            <div class="mt-3">
              <div class="h-1.5 w-full overflow-hidden bg-gray-100 rounded-full dark:bg-gray-800">
                <div class="h-full rounded-full transition-all duration-300"
                  :class="role.is_protected ? 'bg-gray-400' : accent(index).dot"
                  :style="{ width: coverage(role.id) + '%' }"></div>
              </div>
              <p class="mt-1.5 text-[11px] text-gray-400">{{ coverage(role.id) }}% of all permissions</p>
            </div>
          </div>
        </div>
      </ComponentCard>

      <!-- Permission matrix -->
      <ComponentCard title="Permission Matrix" desc="Toggle a cell to grant or revoke a permission. Highlighted cells are unsaved.">
        <template #header>
          <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
              <i class="absolute text-xs -translate-y-1/2 pi pi-search left-3 top-1/2 text-gray-400"></i>
              <input v-model="search" type="text" placeholder="Filter permissions..."
                class="w-56 py-2 pl-9 pr-3 text-sm border border-gray-300 rounded-lg bg-white dark:bg-gray-900 dark:border-gray-700 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 outline-none" />
            </div>
            <button class="px-3 py-2 text-sm font-medium border rounded-lg text-gray-600 border-gray-300 dark:border-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40"
              :disabled="!hasDirty" @click="resetChanges">
              Discard
            </button>
            <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="!hasDirty || saving" @click="save">
              <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'"></i>
              Save changes
              <span v-if="hasDirty" class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-white/20">
                {{ dirtyRoles.length }}
              </span>
            </button>
          </div>
        </template>

        <div v-if="loading" class="py-10 text-center">
          <i class="text-2xl text-blue-600 pi pi-spin pi-spinner"></i>
        </div>

        <div v-else class="overflow-auto border border-gray-200 rounded-xl max-h-[70vh] dark:border-gray-800">
          <table class="min-w-full text-sm border-separate border-spacing-0">
            <thead>
              <tr>
                <th class="sticky left-0 top-0 z-30 bg-gray-50 dark:bg-gray-800/80 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-r border-gray-200 dark:border-gray-700"
                  style="min-width: 240px">
                  Permission
                </th>
                <th v-for="(role, index) in roles" :key="role.id"
                  class="sticky top-0 z-20 bg-gray-50 dark:bg-gray-800/80 px-3 py-2 border-b border-r border-gray-200 dark:border-gray-700 last:border-r-0"
                  style="min-width: 120px">
                  <div class="flex flex-col items-center gap-1">
                    <div class="flex items-center gap-1">
                      <span class="w-2 h-2 rounded-full" :class="accent(index).dot"></span>
                      <span class="max-w-[90px] truncate text-xs font-semibold text-gray-700 dark:text-gray-200">{{ role.name }}</span>
                      <i v-if="role.is_protected" class="text-[9px] pi pi-lock text-gray-400" title="Bypasses all checks"></i>
                    </div>
                    <button v-if="!role.is_protected" type="button"
                      class="text-[10px] font-medium text-blue-600 hover:underline"
                      @click="toggleRoleAll(role.id)">
                      {{ allVisibleGranted(role.id) ? 'Revoke all' : 'Grant all' }}
                    </button>
                    <span v-else class="text-[10px] text-gray-400">Full access</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in filteredGroups" :key="group.key">
                <tr>
                  <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800/80 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-r border-gray-200 dark:border-gray-700">
                    {{ group.label }}
                  </td>
                  <td v-for="role in roles" :key="role.id"
                    class="bg-gray-50 dark:bg-gray-800/80 border-b border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                  </td>
                </tr>
                <tr v-for="permission in group.permissions" :key="permission.name" class="group/row">
                  <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-4 py-2 text-gray-700 capitalize border-b border-r border-gray-100 dark:border-gray-800 group-hover/row:bg-blue-50/50 dark:group-hover/row:bg-blue-900/10">
                    {{ permission.name }}
                  </td>
                  <td v-for="role in roles" :key="role.id"
                    class="px-3 py-1 text-center border-b border-r border-gray-100 dark:border-gray-800 last:border-r-0 group-hover/row:bg-blue-50/50 dark:group-hover/row:bg-blue-900/10">
                    <button type="button" role="checkbox" :aria-checked="isGranted(role.id, permission.name)"
                      :disabled="role.is_protected" :title="role.is_protected ? 'Admin has all permissions' : ''"
                      class="inline-flex items-center justify-center w-5 h-5 mx-auto transition rounded-md border"
                      :class="cellClass(role, permission.name)" @click="toggle(role.id, permission.name)">
                      <i v-if="isGranted(role.id, permission.name)" class="text-[10px] pi pi-check"></i>
                    </button>
                  </td>
                </tr>
              </template>

              <tr v-if="!filteredGroups.length">
                <td :colspan="roles.length + 1" class="px-4 py-8 text-center text-gray-400">
                  No permissions match "{{ search }}".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </ComponentCard>
    </div>

    <!-- Create / rename dialog -->
    <Dialog v-model:visible="dialog.visible" :header="dialog.mode === 'create' ? 'New Role' : 'Rename Role'"
      modal :style="{ width: '28rem' }">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role name</label>
        <InputText v-model="dialog.name" class="w-full" :class="{ 'p-invalid': dialog.error }"
          placeholder="e.g. Product Owner" @keyup.enter="submitRole" />
        <small v-if="dialog.error" class="text-red-500">{{ dialog.error }}</small>
        <p class="text-xs text-gray-400">
          New roles start with no permissions — grant them from the matrix.
        </p>
      </div>
      <template #footer>
        <button class="px-4 py-2 text-sm border rounded-lg text-gray-600 border-gray-300 dark:border-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
          @click="dialog.visible = false">
          Cancel
        </button>
        <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          :disabled="dialog.saving" @click="submitRole">
          <i :class="dialog.saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'"></i>
          {{ dialog.mode === 'create' ? 'Create Role' : 'Save' }}
        </button>
      </template>
    </Dialog>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Swal from 'sweetalert2';
import {
  fetchRoles,
  fetchPermissionCatalog,
  createRole,
  updateRole,
  deleteRole,
} from '@/services/rolesApi';

const ACCENTS = [
  { dot: 'bg-blue-500' },
  { dot: 'bg-emerald-500' },
  { dot: 'bg-violet-500' },
  { dot: 'bg-amber-500' },
  { dot: 'bg-rose-500' },
  { dot: 'bg-sky-500' },
  { dot: 'bg-teal-500' },
  { dot: 'bg-fuchsia-500' },
];

const roles = ref([]);
const groups = ref([]);
const draft = reactive({});
const loading = ref(false);
const saving = ref(false);
const search = ref('');

const dialog = reactive({
  visible: false,
  mode: 'create',
  roleId: null,
  name: '',
  error: '',
  saving: false,
});

const accent = (index) => ACCENTS[index % ACCENTS.length];

const totalPermissions = computed(() =>
  groups.value.reduce((sum, group) => sum + group.permissions.length, 0)
);

const totalMembers = computed(() =>
  roles.value.reduce((sum, role) => sum + (role.users_count || 0), 0)
);

const filteredGroups = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return groups.value;

  return groups.value
    .map((group) => ({
      ...group,
      permissions: group.permissions.filter(
        (permission) =>
          permission.name.toLowerCase().includes(term) ||
          group.label.toLowerCase().includes(term)
      ),
    }))
    .filter((group) => group.permissions.length > 0);
});

const isGranted = (roleId, permission) =>
  (draft[roleId] || []).includes(permission);

const grantedCount = (roleId) => (draft[roleId] || []).length;

const coverage = (roleId) =>
  totalPermissions.value === 0
    ? 0
    : Math.round((grantedCount(roleId) / totalPermissions.value) * 100);

const cellClass = (role, permission) => {
  if (role.is_protected) {
    return 'bg-blue-600 border-blue-600 text-white opacity-80 cursor-not-allowed';
  }
  return isGranted(role.id, permission)
    ? 'bg-blue-600 border-blue-600 text-white'
    : 'border-gray-300 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 cursor-pointer';
};

const visiblePermissions = computed(() =>
  filteredGroups.value.flatMap((group) => group.permissions.map((p) => p.name))
);

const allVisibleGranted = (roleId) =>
  visiblePermissions.value.length > 0 &&
  visiblePermissions.value.every((name) => isGranted(roleId, name));

const originalFor = (role) => [...(role.permissions || [])];

const roleById = (roleId) => roles.value.find((role) => role.id === roleId);

const isDirty = (roleId) => {
  const role = roleById(roleId);
  if (!role || role.is_protected) return false;

  const current = [...(draft[roleId] || [])].sort();
  const original = originalFor(role).sort();

  return current.length !== original.length || current.some((name, i) => name !== original[i]);
};

const dirtyRoles = computed(() =>
  roles.value.filter((role) => isDirty(role.id)).map((role) => role.id)
);

const hasDirty = computed(() => dirtyRoles.value.length > 0);

const toggle = (roleId, permission) => {
  if (roleById(roleId)?.is_protected) return;

  const list = draft[roleId] || (draft[roleId] = []);
  const index = list.indexOf(permission);

  if (index === -1) list.push(permission);
  else list.splice(index, 1);
};

const toggleRoleAll = (roleId) => {
  const list = draft[roleId] || (draft[roleId] = []);

  if (allVisibleGranted(roleId)) {
    const visible = new Set(visiblePermissions.value);
    draft[roleId] = list.filter((name) => !visible.has(name));
  } else {
    const merged = new Set(list);
    visiblePermissions.value.forEach((name) => merged.add(name));
    draft[roleId] = [...merged];
  }
};

const resetChanges = () => {
  roles.value.forEach((role) => {
    draft[role.id] = originalFor(role);
  });
};

const buildDraft = () => {
  Object.keys(draft).forEach((key) => delete draft[key]);
  roles.value.forEach((role) => {
    draft[role.id] = originalFor(role);
  });
};

const load = async () => {
  loading.value = true;
  try {
    const [rolesResponse, catalogResponse] = await Promise.all([
      fetchRoles(),
      fetchPermissionCatalog(),
    ]);
    roles.value = rolesResponse.data.data || [];
    groups.value = catalogResponse.data.data || [];
    buildDraft();
  } catch (error) {
    console.error('Failed to load roles:', error);
    Swal.fire('Error', 'Failed to load roles and permissions.', 'error');
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  if (!hasDirty.value) return;

  saving.value = true;
  const changed = [...dirtyRoles.value];

  try {
    await Promise.all(
      changed.map((roleId) => updateRole(roleId, { permissions: [...(draft[roleId] || [])] }))
    );

    changed.forEach((roleId) => {
      const role = roleById(roleId);
      if (role) role.permissions = [...(draft[roleId] || [])];
    });

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Permissions updated',
      showConfirmButton: false,
      timer: 2000,
    });
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to save changes.', 'error');
  } finally {
    saving.value = false;
  }
};

const openCreate = () => {
  dialog.mode = 'create';
  dialog.roleId = null;
  dialog.name = '';
  dialog.error = '';
  dialog.visible = true;
};

const openRename = (role) => {
  dialog.mode = 'rename';
  dialog.roleId = role.id;
  dialog.name = role.name;
  dialog.error = '';
  dialog.visible = true;
};

const submitRole = async () => {
  const name = dialog.name.trim();
  if (!name) {
    dialog.error = 'Role name is required.';
    return;
  }

  dialog.saving = true;
  try {
    if (dialog.mode === 'create') {
      await createRole({ name, permissions: [] });
    } else {
      await updateRole(dialog.roleId, { name });
    }

    dialog.visible = false;
    await load();
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: dialog.mode === 'create' ? 'Role created' : 'Role renamed',
      showConfirmButton: false,
      timer: 2000,
    });
  } catch (error) {
    const backendErrors = error.response?.data?.errors;
    dialog.error = backendErrors?.name?.[0] || error.response?.data?.message || 'Failed to save role.';
  } finally {
    dialog.saving = false;
  }
};

const confirmDelete = async (role) => {
  const result = await Swal.fire({
    title: 'Delete Role?',
    text: `Delete "${role.name}"? Users must not be assigned to it.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete',
  });

  if (!result.isConfirmed) return;

  try {
    await deleteRole(role.id);
    await load();
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Role deleted',
      showConfirmButton: false,
      timer: 2000,
    });
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to delete role.', 'error');
  }
};

onMounted(load);
</script>
