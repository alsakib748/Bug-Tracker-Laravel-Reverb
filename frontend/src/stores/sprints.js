import { defineStore } from 'pinia'
import {
  addIssuesToSprint,
  cancelSprint,
  completeSprint,
  createSprint,
  fetchSprintReport,
  fetchSprints,
  removeIssueFromSprint,
  startSprint,
  updateSprint,
} from '@/services/sprintsApi'

export const useSprintStore = defineStore('sprints', {
  state: () => ({
    sprints: [],
    report: null,
    loading: false,
    saving: false,
  }),
  getters: {
    activeSprint: (state) =>
      state.sprints.find((sprint) => sprint.status === 'active') || null,
    plannedSprints: (state) =>
      state.sprints.filter((sprint) => sprint.status === 'planned'),
  },
  actions: {
    async loadSprints(projectId) {
      this.loading = true
      try {
        const response = await fetchSprints(projectId)
        this.sprints = response.data.data
        return this.sprints
      } finally {
        this.loading = false
      }
    },

    async create(projectId, payload) {
      this.saving = true
      try {
        const response = await createSprint(projectId, payload)
        await this.loadSprints(projectId)
        return response.data.data
      } finally {
        this.saving = false
      }
    },

    async update(sprintId, payload, projectId) {
      this.saving = true
      try {
        const response = await updateSprint(sprintId, payload)
        if (projectId) await this.loadSprints(projectId)
        return response.data.data
      } finally {
        this.saving = false
      }
    },

    async start(sprintId, projectId) {
      this.saving = true
      try {
        const response = await startSprint(sprintId)
        if (projectId) await this.loadSprints(projectId)
        return response.data.data
      } finally {
        this.saving = false
      }
    },

    async complete(sprintId, payload, projectId) {
      this.saving = true
      try {
        const response = await completeSprint(sprintId, payload)
        if (projectId) await this.loadSprints(projectId)
        return response.data.data
      } finally {
        this.saving = false
      }
    },

    async cancel(sprintId, projectId) {
      this.saving = true
      try {
        const response = await cancelSprint(sprintId)
        if (projectId) await this.loadSprints(projectId)
        return response.data.data
      } finally {
        this.saving = false
      }
    },

    async addIssues(sprintId, issueIds) {
      await addIssuesToSprint(sprintId, issueIds)
    },

    async removeIssue(sprintId, issueId) {
      await removeIssueFromSprint(sprintId, issueId)
    },

    async loadReport(sprintId) {
      const response = await fetchSprintReport(sprintId)
      this.report = response.data.data
      return this.report
    },
  },
})
