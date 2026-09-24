import { http } from './client'

export const listTickets = (page, signal) => http.get('/api/v1/tickets', {
  params: { page, per_page: 5 }, signal,
}).then(r => r.data)

export const getTicket = (id, signal) => http.get(`/api/v1/tickets/${id}`, { signal }).then(r => r.data.data)

export const getCategories = signal => http.get('/api/v1/categories', { signal }).then(r => r.data.data)

export const createTicket = payload => http.post('/api/v1/tickets', payload).then(r => r.data.data)
