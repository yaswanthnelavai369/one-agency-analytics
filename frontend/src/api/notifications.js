import { apiClient } from './client';

export const notificationsApi = {
  catalogue: () => apiClient.get('/agency/notifications/catalogue').then((r) => r.data),
  list: () => apiClient.get('/agency/notifications').then((r) => r.data),
  update: (channel, data) => apiClient.put(`/agency/notifications/${channel}`, data).then((r) => r.data),
  test: (channel) => apiClient.post(`/agency/notifications/${channel}/test`).then((r) => r.data),
  logs: () => apiClient.get('/agency/notifications/logs').then((r) => r.data),
};
