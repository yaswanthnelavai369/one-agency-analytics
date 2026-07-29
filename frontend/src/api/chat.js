import { apiClient } from './client';

export const chatApi = {
  getThread: (clientId) => apiClient.get(`/agency/clients/${clientId}/chat`).then((r) => r.data),
  sendMessage: (clientId, message) => apiClient.post(`/agency/clients/${clientId}/chat/messages`, { message }).then((r) => r.data),
};
