import { apiClient } from './client';

export const goalsApi = {
  catalogue: () => apiClient.get('/agency/goals/catalogue').then((r) => r.data),
  list: (clientId, status) => apiClient.get(`/agency/clients/${clientId}/goals`, { params: { status } }).then((r) => r.data),
  create: (clientId, data) => apiClient.post(`/agency/clients/${clientId}/goals`, data).then((r) => r.data),
  get: (clientId, goalId) => apiClient.get(`/agency/clients/${clientId}/goals/${goalId}`).then((r) => r.data),
  update: (clientId, goalId, data) => apiClient.put(`/agency/clients/${clientId}/goals/${goalId}`, data).then((r) => r.data),
  addProgress: (clientId, goalId, value, mode = 'set') =>
    apiClient.post(`/agency/clients/${clientId}/goals/${goalId}/progress`, { value, mode }).then((r) => r.data),
  recompute: (clientId, goalId) => apiClient.post(`/agency/clients/${clientId}/goals/${goalId}/recompute`).then((r) => r.data),
  archive: (clientId, goalId) => apiClient.post(`/agency/clients/${clientId}/goals/${goalId}/archive`).then((r) => r.data),
  remove: (clientId, goalId) => apiClient.delete(`/agency/clients/${clientId}/goals/${goalId}`).then((r) => r.data),
};
