import { apiClient } from './client';

export const anomaliesApi = {
  list: (clientId, status) => apiClient.get(`/agency/clients/${clientId}/anomalies`, { params: { status } }).then((r) => r.data),
  detect: (clientId) => apiClient.post(`/agency/clients/${clientId}/anomalies/detect`).then((r) => r.data),
  acknowledge: (clientId, anomalyId) =>
    apiClient.post(`/agency/clients/${clientId}/anomalies/${anomalyId}/acknowledge`).then((r) => r.data),
  resolve: (clientId, anomalyId) =>
    apiClient.post(`/agency/clients/${clientId}/anomalies/${anomalyId}/resolve`).then((r) => r.data),
};
