import { apiClient } from './client';

export const integrationsApi = {
  catalogue: () => apiClient.get('/agency/integrations/catalogue').then((r) => r.data),
  list: (clientId) => apiClient.get(`/agency/clients/${clientId}/integrations`).then((r) => r.data),
  connect: (clientId, provider) =>
    apiClient.post(`/agency/clients/${clientId}/integrations/${provider}/connect`).then((r) => r.data),
  syncNow: (clientId, integrationId) =>
    apiClient.post(`/agency/clients/${clientId}/integrations/${integrationId}/sync`).then((r) => r.data),
  disconnect: (clientId, integrationId) =>
    apiClient.delete(`/agency/clients/${clientId}/integrations/${integrationId}`).then((r) => r.data),
};
