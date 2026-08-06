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
        const response = await api.get(`/api/issues/${issueId}/comments`)
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
        const response = await api.post(`/api/issues/${issueId}/comments`, { comment: content })
        const newComment = response.data.data
        this.comments.push(newComment)
        return newComment
      } catch (error) {
        console.error('Failed to create comment:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateComment(commentId, content) {
      try {
        const response = await api.put(`/api/comments/${commentId}`, { comment: content })

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
        await api.delete(`/api/comments/${commentId}`)
        this.comments = this.comments.filter((c) => c.id !== commentId)
      } catch (error) {
        console.error('Failed to delete comment: ', error)
        throw error
      }
    },
    initializeListeners(issueId) {
      if (!issueId) return
      echo.private(`private-issue.${issueId}`).listen('comment.created', (data) => {
        //  Add new comment to the list
        this.comments.push({
          id: data.id,
          comment: data.comment,
          user_id: data.user_id,
          user: { name: data.user_name },
          created_at: data.created_at,
        })
      })
    },
  },
})
