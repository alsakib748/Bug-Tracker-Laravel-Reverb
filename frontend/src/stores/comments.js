import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'

export const useCommentStore = defineStore('comments', {
  state: () => ({
    comments: [],
    loading: false,
  }),
  actions: {
    async fetchComments(issueId) {
      this.loading = true
      try {
        // const response = await api.get(`/api/issues/${issueId}/comments`)
        const response = await api.get(`/issues/${issueId}/comments`)
        this.comments = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch comments: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createComment(issueId, content) {
      this.loading = true
      try {
        // const response = await api.post(`/api/issues/${issueId}/comments`, { comment: content })
        const response = await api.post(`/issues/${issueId}/comments`, { comment: content })
        const newComment = response.data.data
        this.comments.push(newComment)
        return newComment
      } catch (error) {
        console.error('Failed to create comment:', error.response?.status, error.response?.data)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateComment(commentId, content) {
      try {
        // const response = await api.put(`/api/comments/${commentId}`, { comment: content })
        const response = await api.put(`/comments/${commentId}`, { comment: content })

        const updated = response.data.data
        const index = this.comments.findIndex((c) => c.id === commentId)
        if (index !== -1) {
          this.comments[index] = updated
        }
        return updated
      } catch (error) {
        console.error('Failed to update comment: ', error)
        throw error
      }
    },
    async deleteComment(commentId) {
      try {
        // await api.delete(`/api/comments/${commentId}`)
        await api.delete(`/comments/${commentId}`)
        this.comments = this.comments.filter((c) => c.id !== commentId)
      } catch (error) {
        console.error('Failed to delete comment: ', error)
        throw error
      }
    },

    initializeListeners(issueId) {
      if (!issueId) return
      // The actual subscription is set up in the page (Show.vue) via
      // subscribeToIssueChannel(). Keeping this hook around so callers
      // that still reference it don't crash, but no listener is added.
    },

    stopListeners(issueId) {
      if (!issueId) return
      echo.leave(`issue.${issueId}`)
    },
  },
})
