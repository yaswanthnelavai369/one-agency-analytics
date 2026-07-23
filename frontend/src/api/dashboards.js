import { apiClient } from './client';

export const dashboardsApi = {
  widgetCatalogue: () => apiClient.get('/agency/dashboards/widget-catalogue').then((r) => r.data),
  list: (clientId) => apiClient.get('/agency/dashboards', { params: { client_id: clientId } }).then((r) => r.data),
  get: (id) => apiClient.get(`/agency/dashboards/${id}`).then((r) => r.data),
  create: (data) => apiClient.post('/agency/dashboards', data).then((r) => r.data),
  update: (id, data) => apiClient.put(`/agency/dashboards/${id}`, data).then((r) => r.data),
  duplicate: (id, name) => apiClient.post(`/agency/dashboards/${id}/duplicate`, { name }).then((r) => r.data),
  reset: (id) => apiClient.post(`/agency/dashboards/${id}/reset`).then((r) => r.data),
  remove: (id) => apiClient.delete(`/agency/dashboards/${id}`).then((r) => r.data),
  addWidget: (id, widgetType) => apiClient.post(`/agency/dashboards/${id}/widgets`, { widget_type: widgetType }).then((r) => r.data),
  removeWidget: (id, widgetId) => apiClient.delete(`/agency/dashboards/${id}/widgets/${widgetId}`).then((r) => r.data),
  savePositions: (id, positions) =>
    apiClient.put(`/agency/dashboards/${id}/widgets/positions`, { positions }).then((r) => r.data),
};
