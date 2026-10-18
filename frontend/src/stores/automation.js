import { defineStore } from 'pinia'
import {
  createAutomationRule,
  deleteAutomationRule,
  disableAutomationRule,
  enableAutomationRule,
  fetchAutomationOptions,
  fetchAutomationRule,
  fetchProjectAutomationExecutions,
  fetchProjectRules,
  updateAutomationRule,
} from '@/services/automationApi'

export const useAutomationStore = defineStore('automation', {
  state: () => ({
    options: { triggers: [], operators: [], actions: [], fields: {} },
    rules: [],
    rule: null,
    executions: [],
    pagination: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    loading: false,
    saving: false,
  }),
  getters: {
    triggerOptions: (state) => state.options.triggers || [],
    operatorOptions: (state) => state.options.operators || [],
    actionOptions: (state) => state.options.actions || [],
    fieldDefinitions: (state) => state.options.fields || {},
  },
  actions: {
    async loadOptions() {
      if (this.options.triggers.length) return this.options
      const { data } = await fetchAutomationOptions()
      this.options = data
      return this.options
    },

    async loadRules(projectId) {
      this.loading = true
      try {
        const { data } = await fetchProjectRules(projectId)
        this.rules = data.data
        return this.rules
      } finally {
        this.loading = false
      }
    },

    async create(projectId, payload) {
      this.saving = true
      try {
        const { data } = await createAutomationRule(projectId, payload)
        return data.data
      } finally {
        this.saving = false
      }
    },

    async loadRule(ruleId) {
      this.loading = true
      try {
        const { data } = await fetchAutomationRule(ruleId)
        this.rule = data.data
        return this.rule
      } finally {
        this.loading = false
      }
    },

    async update(ruleId, payload) {
      this.saving = true
      try {
        const { data } = await updateAutomationRule(ruleId, payload)
        this.replace(data.data)
        return data.data
      } finally {
        this.saving = false
      }
    },

    async toggle(rule) {
      const { data } = rule.is_enabled
        ? await disableAutomationRule(rule.id)
        : await enableAutomationRule(rule.id)

      this.replace(data.data)
      return data.data
    },

    async remove(ruleId) {
      await deleteAutomationRule(ruleId)
      this.rules = this.rules.filter((rule) => rule.id !== ruleId)
    },

    async loadExecutions(projectId, params = {}) {
      const { data } = await fetchProjectAutomationExecutions(projectId, params)
      this.executions = data.data
      if (data.meta) this.pagination = data.meta
      return this.executions
    },

    setRule(rule) {
      this.rule = rule
    },

    replace(rule) {
      this.rule = rule
      const index = this.rules.findIndex((item) => item.id === rule.id)
      if (index !== -1) this.rules.splice(index, 1, rule)
    },
  },
})

/**
 * A blank rule definition used by the create form and the rule builder.
 */
export const blankRule = (projectId = null) => ({
  project_id: projectId,
  name: '',
  description: '',
  trigger_type: 'issue.created',
  conditions: { match: 'all', rules: [] },
  actions: [],
  is_enabled: true,
  run_on_actor_changes: false,
  cooldown_seconds: null,
})
