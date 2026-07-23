import { apiClient } from './client';

export const clientsApi = {
  list: (params) => apiClient.get('/agency/clients', { params }).then((r) => r.data),
  create: (data) => apiClient.post('/agency/clients', data).then((r) => r.data),
  get: (id) => apiClient.get(`/agency/clients/${id}`).then((r) => r.data),
  update: (id, data) => apiClient.put(`/agency/clients/${id}`, data).then((r) => r.data),
  remove: (id) => apiClient.delete(`/agency/clients/${id}`).then((r) => r.data),
};
