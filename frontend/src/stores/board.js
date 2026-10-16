import { defineStore } from 'pinia'
import { fetchBoard, moveBoardIssue } from '@/services/boardApi'

export const useBoardStore = defineStore('board', {
  state: () => ({
    columns: [],
    projectId: null,
    sprintId: null,
    loading: false,
    moving: false,
  }),
  getters: {
    totalIssues: (state) =>
      state.columns.reduce((sum, column) => sum + column.issues.length, 0),
  },
  actions: {
    async loadBoard(projectId, sprintId = null) {
      this.loading = true
      this.projectId = projectId
      this.sprintId = sprintId
      try {
        const response = await fetchBoard(projectId, sprintId)
        this.columns = response.data.data.columns
        return this.columns
      } finally {
        this.loading = false
      }
    },

    findIssue(issueId) {
      for (const column of this.columns) {
        const index = column.issues.findIndex((issue) => issue.id === issueId)
        if (index !== -1) return { column, index }
      }
      return null
    },

    // The draggable component mutates the column arrays optimistically, so this
    // only has to persist the move and roll the board back on failure.
    async persistMove(issue, status, position) {
      this.moving = true
      try {
        const response = await moveBoardIssue(issue.id, status, position)
        const updated = response.data.data
        const located = this.findIssue(updated.id)

        if (located) {
          Object.assign(located.column.issues[located.index], {
            status: updated.status,
            status_label: updated.status_label,
            status_color: updated.status_color,
            board_position: updated.board_position,
            version: updated.version,
          })
        }

        return updated
      } catch (error) {
        if (this.projectId) {
          await this.loadBoard(this.projectId, this.sprintId)
        }
        throw error
      } finally {
        this.moving = false
      }
    },
  },
})
