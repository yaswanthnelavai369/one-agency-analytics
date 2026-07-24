import { apiClient } from './client';

export const aiChatApi = {
  quickPrompts: () => apiClient.get('/agency/ai-chat/quick-prompts').then((r) => r.data),
  getConversation: (clientId) => apiClient.get(`/agency/clients/${clientId}/ai-chat`).then((r) => r.data),
  sendMessage: (clientId, message) =>
    apiClient.post(`/agency/clients/${clientId}/ai-chat/messages`, { message }).then((r) => r.data),
};
