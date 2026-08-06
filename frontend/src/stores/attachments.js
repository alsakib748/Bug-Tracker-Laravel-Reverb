import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'

export const useAttachmentStore = defineStore('attachments', {
  state: () => ({
    attachments: [],
    loading: false,
    uploading: false,
  }),
  actions: {
    async fetchAttachments(issueId) {
      this.loading = true
      try {
        const response = await api.get(`/api/issues/${issueId}/attachments`)
        this.attachments = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch attachments:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async uploadAttachment(issueId, file) {
      this.uploading = true
      try {
        const formData = new FormData()
        formData.append('file', file)
        const response = await api.post(`/api/issues/${issueId}/attachments`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        const newAtt = response.data.data
        this.attachments.push(newAtt)
        return newAtt
      } catch (error) {
        console.error('Failed to upload attachment:', error)
        throw error
      } finally {
        this.uploading = false
      }
    },
    async deleteAttachment(attachmentId) {
      try {
        await api.delete(`/api/attachments/${attachmentId}`)
        this.attachments = this.attachments.filter((a) => a.id !== attachmentId)
      } catch (error) {
        console.error('Failed to delete attachment:', error)
        throw error
      }
    },
    initializeListeners(issueId) {
      if (!issueId) return
      echo
        .private(`private-issue.${issueId}`)
        .listen('attachment.uploaded', (data) => {
          this.attachments.push(data)
        })
        .listen('attachment.delete', (data) => {
          this.attachments = this.attachments.filter((a) => a.id !== data.id)
        })
    },
  },
})
