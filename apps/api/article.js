import { get, post, put, del } from '@/utils/request.js'

export const articleApi = {
  list: (params) => get('/articles', { params }),
  detail: (id) => get(`/articles/${id}`),
  create: (data) => post('/articles', data),
  update: (id, data) => put(`/articles/${id}`, data),
  remove: (id) => del(`/articles/${id}`),
  submit: (id) => post(`/articles/${id}/submit`),
}
