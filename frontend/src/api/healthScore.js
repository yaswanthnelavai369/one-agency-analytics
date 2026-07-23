import { apiClient } from './client';

export const healthScoreApi = {
  get: (clientId) => apiClient.get(`/agency/clients/${clientId}/health-score`).then((r) => r.data),
  recalculate: (clientId) => apiClient.post(`/agency/clients/${clientId}/health-score/recalculate`).then((r) => r.data),
};
