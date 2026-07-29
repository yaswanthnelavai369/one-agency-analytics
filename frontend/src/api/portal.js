import { apiClient } from './client';

export const portalApi = {
  me: () => apiClient.get('/client/me').then((r) => r.data),
  dashboard: () => apiClient.get('/client/dashboard').then((r) => r.data),
  healthScore: () => apiClient.get('/client/health-score').then((r) => r.data),

  integrationsCatalogue: () => apiClient.get('/client/integrations/catalogue').then((r) => r.data),
  integrations: () => apiClient.get('/client/integrations').then((r) => r.data),
  connectIntegration: (provider) => apiClient.post(`/client/integrations/${provider}/connect`).then((r) => r.data),
  disconnectIntegration: (integrationId) => apiClient.delete(`/client/integrations/${integrationId}`).then((r) => r.data),

  quickPrompts: () => apiClient.get('/client/ai-chat/quick-prompts').then((r) => r.data),
  getConversation: () => apiClient.get('/client/ai-chat').then((r) => r.data),
  sendMessage: (message) => apiClient.post('/client/ai-chat/messages', { message }).then((r) => r.data),

  alerts: () => apiClient.get('/client/alerts').then((r) => r.data),

  goalsCatalogue: () => apiClient.get('/client/goals/catalogue').then((r) => r.data),
  goals: (status) => apiClient.get('/client/goals', { params: { status } }).then((r) => r.data),
  createGoal: (data) => apiClient.post('/client/goals', data).then((r) => r.data),

  getChat: () => apiClient.get('/client/chat').then((r) => r.data),
  sendChatMessage: (message) => apiClient.post('/client/chat/messages', { message }).then((r) => r.data),
};
